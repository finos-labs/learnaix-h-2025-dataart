from typing import List

from pydantic import BaseModel


class ValidationErrorSchema(BaseModel):
    """Validation error schema."""

    localization: str
    message: str
    input: str
    code: str = "validation_error"


class ErrorResponseSchema(BaseModel):
    """Error response schema."""

    message: str
    code: str


class ValidationErrorResponseSchema(BaseModel):
    """Validation error response schema."""

    errors: List[ErrorResponseSchema]


class SuccessResponseSchema(BaseModel):
    """Success response schema."""

    message: str = "Success"
    code: str = "OK"
