from logging import DEBUG, Formatter, StreamHandler, getLogger


def setup_logging():
    g_logger = getLogger("general")
    g_logger.setLevel(DEBUG)

    g_handler = StreamHandler()
    g_handler.setLevel(DEBUG)

    g_handler.setFormatter(Formatter("%(asctime)s - %(levelname)s - %(message)s"))
    g_logger.addHandler(g_handler)
