from os import getenv

from pydantic import ConfigDict

SNOWFLAKE_HOST = getenv("SNOWFLAKE_HOST")
SNOWFLAKE_ACCOUNT = getenv("SNOWFLAKE_ACCOUNT")


class BaseSchema:
    """
    Base model with configuration for schema classes
    The from_attribute property allows to read from SQLAlchemy models.
    """

    model_config = ConfigDict(from_attributes=True, extra="forbid")
