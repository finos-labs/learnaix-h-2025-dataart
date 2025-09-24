from typing import List

from app.common import BaseSchema
from app.question.enums import QuestionStatus
from pydantic import BaseModel


class GenerateQuestionsPayload(BaseModel, BaseSchema):
    """Model defining payload structure for question generating endpoint."""

    filename: str


class QuestionDetails(BaseModel, BaseSchema):
    """Model representing a single generated question."""

    id: int
    status: QuestionStatus
    text: str


class GeneratedQuestionsContent(BaseModel, BaseSchema):
    """Generated questions response content."""

    content: List[QuestionDetails]


class GenerateQuestionsResponse(BaseModel, BaseSchema):
    """Response model for generating questions request."""

    delta: GeneratedQuestionsContent


class UpdateQuestionPayload(BaseModel, BaseSchema):
    """Model defining payload structure for question update endpoint."""

    status: QuestionStatus = None
    text: str = None
