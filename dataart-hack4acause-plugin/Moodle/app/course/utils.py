from logging import getLogger

logger = getLogger("general")


def write_course_to_snowflake(course_id: int):
    """Write a course to the database."""
    logger.info(f"Writing course {course_id}")
