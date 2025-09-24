from app.course.schema import CourseQuestionsResponse
from app.course.utils import write_course_to_snowflake
from app.question.enums import QuestionStatus
from fastapi import APIRouter, BackgroundTasks

course_router = APIRouter(prefix="/api/course", tags=["course"])


@course_router.get(
    path="/{course_id}/questions",
    status_code=200,
    response_model=CourseQuestionsResponse,
)
async def list_course_questions(course_id: int, background_tasks: BackgroundTasks):
    """Retrieve and return questions generated for the given course."""
    background_tasks.add_task(write_course_to_snowflake, course_id=1)
    background_tasks.add_task(write_course_to_snowflake, course_id=2)

    return {
        "id": course_id,
        "questions": [
            {"id": 1, "status": QuestionStatus.CREATED, "text": "Question 1"},
            {"id": 2, "status": QuestionStatus.CREATED, "text": "Question 2"},
            {"id": 3, "status": QuestionStatus.CREATED, "text": "Question 3"},
            {"id": 4, "status": QuestionStatus.CREATED, "text": "Question 4"},
        ],
    }
