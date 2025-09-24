from typing import List

from app.common import BaseSchema
from app.question.schema import QuestionDetails
from pydantic import BaseModel


class CourseQuestionsResponse(BaseModel, BaseSchema):
    """Course generated questions response"""

    id: int
    questions: List[QuestionDetails]
