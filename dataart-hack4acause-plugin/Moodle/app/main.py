import PyPDF2
from io import BytesIO

from http import HTTPStatus
from logging import getLogger

from app.course.views import course_router
from app.logging import setup_logging
from app.question.views import execute_router, question_router
from fastapi import FastAPI, UploadFile, File
from fastapi.exceptions import HTTPException, RequestValidationError
from fastapi.openapi.utils import get_openapi
from fastapi.responses import JSONResponse

from app.snowflake.client import execute_snowflake_query, send_agent_request, parse_sse_response

setup_logging()
logger = getLogger("general")

app = FastAPI()


@app.exception_handler(RequestValidationError)
async def request_validation_exception_handler(_, exc) -> JSONResponse:
    """
    Generic Request Validation exception handler
    :param _: Request object
    :param exc: Exception object.
    :return: JSONResponse
    """
    response_body = {"errors": []}
    for error in exc.errors():
        logger.error("Error happened")
        logger.error(error)
        location = error["loc"]
        formatted = {
            "message": error["msg"],
            "code": "validation_error",
        }
        if len(location) == 2:
            formatted["location"] = location[1]

        response_body["errors"].append(formatted)

    return JSONResponse(content=response_body, status_code=HTTPStatus.BAD_REQUEST)


@app.exception_handler(HTTPException)
async def http_exception_handler(_, exc) -> JSONResponse:
    """
    Generic HTTPException handler
    :param request: Request object
    :param exc: Exception object.
    :return: JSONResponse
    """
    try:
        detailed_exception = exc.detail.model_dump()
    except Exception as e:
        detailed_exception = e
    return JSONResponse(
        content={"errors": [detailed_exception]}, status_code=exc.status_code
    )


@app.get(path="/healthcheck", tags=["Healthcheck"])
async def healthcheck() -> JSONResponse:
    """
    Health check endpoint .
    :return: JSONResponse
    """
    return JSONResponse(status_code=HTTPStatus.OK, content={"status": "ok"})


@app.post(path="/upload", tags=["FileOperation"])
async def upload_file(
    file: UploadFile = File(...)
) -> JSONResponse:
    """
    Health check endpoint.
    :return: JSONResponse
    """
    try:
        if not file.filename.lower().endswith('.pdf'):
            return JSONResponse(
                status_code=400,
                content={"status": "error", "message": "Only PDF files are supported"}
            )

        contents = await file.read()
        with open(file.filename, "wb") as f:
            f.write(contents)

        pdf_stream = BytesIO(contents)
        pdf_reader = PyPDF2.PdfReader(pdf_stream)
        if pdf_reader.is_encrypted:
            return JSONResponse(
                status_code=400,
                content={"status": "error", "message": "PDF is encrypted and cannot be read"}
            )

        # Extract text from all pages
        text_content = ""
        for page_num in range(len(pdf_reader.pages)):
            page = pdf_reader.pages[page_num]
            page_text = page.extract_text()
            if page_text:
                text_content += page_text + "\n"

        stage_name = "@MOODLE_APP.PUBLIC.MOODLE_COURCES_STAGE"
        file_stage_name = f"{stage_name}/{file.filename}"
        put_sql = f"""
            PUT 'file://{file.filename}' {stage_name} 
            AUTO_COMPRESS = FALSE 
            OVERWRITE = TRUE;
        """
        data = execute_snowflake_query(put_sql)
        print(data)

        # tried to parse content via cortex parsed_document however it returns NULL, meanwhile it works through UI
        # parse_sql = f"""
        #     SELECT SNOWFLAKE.CORTEX.PARSE_DOCUMENT(
        #         '{stage_name}',
        #         '{file.filename}',
        #         {{'mode': 'OCR'}}
        #     )['content']::STRING AS content
        # """
        # parsed_content = execute_snowflake_query(parse_sql)
        # print(parsed_content)

        expected_output = """
            "questions" : [
                {
                    "question": "Some question",
                    "answer": "Some answer"
                },
                {
                    "question": "Some question",
                    "answer": "Some answer"
                },
            ]
        """
        #prompt = f"Please generate 10 questions for the following content: {text_content}. Expected output: {expected_output}"
        prompt = f"Please generate 10 random questions. Expected output: {expected_output}"
        semantic_model_file = f"@MOODLE_APP.PUBLIC.MOODLE_STAGE/revenue_timeseries.yaml"
        response = send_agent_request(semantic_model_file, prompt)
        quiz = parse_sse_response(response.text)

        return JSONResponse(
            status_code=HTTPStatus.OK,
            content={
                "status": "ok",
                "status_code": str(response.status_code),
                "file_name": file_stage_name,
                "quiz": quiz,
            }
        )
    except Exception as e:
        return JSONResponse(
            status_code=500,
            content={"status": "error", "message": str(e)}
        )


def custom_openapi():
    if app.openapi_schema:
        return app.openapi_schema
    openapi_schema = get_openapi(
        title="NatWest Hack4aCause API documentation",
        version="0.0.1",
        description="This is a documentation for NatWest Hack4aCause API",
        routes=app.routes,
    )
    for path in openapi_schema["paths"]:
        for method in openapi_schema["paths"][path]:
            # Remove default 422 response
            if openapi_schema["paths"][path][method]["responses"].get("422"):
                openapi_schema["paths"][path][method]["responses"].pop("422")
    app.openapi_schema = openapi_schema
    return app.openapi_schema


app.include_router(execute_router)
app.include_router(question_router)
app.include_router(course_router)

app.openapi = custom_openapi
