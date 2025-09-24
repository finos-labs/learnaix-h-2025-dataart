from app.question.enums import QuestionStatus
from app.question.schema import (
    GenerateQuestionsPayload,
    GenerateQuestionsResponse,
    QuestionDetails,
    UpdateQuestionPayload,
)
from fastapi import APIRouter

execute_router = APIRouter(prefix="/api/execute", tags=["question"])
question_router = APIRouter(prefix="/api/question", tags=["question"])


@execute_router.post(
    path="",
    status_code=200,
    response_model=GenerateQuestionsResponse,
)
async def generate_questions(payload: GenerateQuestionsPayload):
    """Generate and return questions."""

    return {
        "delta": {
            "content": [
                {"id": 1, "status": QuestionStatus.CREATED, "text": "Question 1"},
                {"id": 2, "status": QuestionStatus.CREATED, "text": "Question 2"},
                {"id": 3, "status": QuestionStatus.CREATED, "text": "Question 3"},
                {"id": 4, "status": QuestionStatus.CREATED, "text": "Question 4"},
            ]
        }
    }


@question_router.post(
    path="/{question_id}/approve",
    status_code=200,
    response_model=QuestionDetails,
)
async def approve_question(question_id: int):
    """Approve and return single question."""

    return {
        "id": question_id,
        "status": QuestionStatus.APPROVED,
        "text": f"Question {question_id}",
    }


@question_router.post(
    path="/{question_id}/reject",
    status_code=200,
    response_model=QuestionDetails,
)
async def reject_question(question_id: int):
    """Reject and return single question."""

    return {
        "id": question_id,
        "status": QuestionStatus.REJECTED,
        "text": f"Question {question_id}",
    }


@question_router.get(
    path="/{question_id}",
    status_code=200,
    response_model=QuestionDetails,
)
async def retrieve_question(question_id: int):
    """Retrieve and return single question."""

    return {
        "id": question_id,
        "status": QuestionStatus.CREATED,
        "text": f"Question {question_id}",
    }


@question_router.patch(
    path="/{question_id}",
    status_code=200,
    response_model=QuestionDetails,
)
async def update_question(question_id: int, payload: UpdateQuestionPayload):
    """Retrieve and return single question."""

    return {
        "id": question_id,
        "status": QuestionStatus.CREATED,
        "text": f"Question {question_id}",
        **payload.model_dump(exclude_unset=True),
    }
