from enum import Enum


class QuestionStatus(str, Enum):
    """Question status."""

    APPROVED = "approved"
    CREATED = "created"
    REJECTED = "rejected"
