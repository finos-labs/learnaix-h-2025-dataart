from http import HTTPStatus

from fastapi import HTTPException

from app.errors import HttpErrorDetail

UnauthorizedException = HTTPException(
    status_code=HTTPStatus.UNAUTHORIZED,
    detail=HttpErrorDetail(message="Missing authorization token", code="unauthorized"),
)
ForbiddenException = HTTPException(
    status_code=HTTPStatus.FORBIDDEN,
    detail=HttpErrorDetail(
        message="Missing permission to perform this action", code="forbidden"
    ),
)
