from pydantic import BaseModel


class HttpErrorDetail(BaseModel):
    """Model defining custom detail schema for HTTPException."""

    message: str
    code: str
