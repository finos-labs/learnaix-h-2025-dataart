import snowflake.connector
import os
import json
import requests


def get_snowflake_connection():
    """Create Snowflake connection"""
    try:
        if os.path.isfile("/snowflake/session/token"):
            creds = {
                'host': os.getenv('SNOWFLAKE_HOST'),
                'port': os.getenv('SNOWFLAKE_PORT'),
                'protocol': "https",
                'account': os.getenv('SNOWFLAKE_ACCOUNT'),
                'authenticator': "oauth",
                'token': get_login_token(),
                'warehouse': os.getenv('SNOWFLAKE_WAREHOUSE', 'COMPUTE_WH'),
                'database': os.getenv('SNOWFLAKE_DATABASE', 'MOODLE_APP'),
                'schema': os.getenv('SNOWFLAKE_SCHEMA', 'PUBLIC'),
                'client_session_keep_alive': True
            }
        else:
            creds = {
                'user': os.getenv('SNOWFLAKE_USER', 'user'),
                'password': os.getenv('SNOWFLAKE_PASSWORD', 'pwd'),
                'account': os.getenv('SNOWFLAKE_ACCOUNT'),
                'warehouse': os.getenv('SNOWFLAKE_WAREHOUSE', 'COMPUTE_WH'),
                'database': os.getenv('SNOWFLAKE_DATABASE', 'MOODLE_APP'),
                'schema': os.getenv('SNOWFLAKE_SCHEMA', 'PUBLIC'),
                'client_session_keep_alive': True
            }
        conn = snowflake.connector.connect(**creds)
        return conn
    except Exception as e:
        raise e


def execute_snowflake_query(sql_query: str):
    conn = get_snowflake_connection()
    with conn.cursor() as cur:
        cur.execute(sql_query)
        data = cur.fetchall()
        return data


def get_login_token():
    """Fetches the SPCS OAuth token"""
    with open("/snowflake/session/token", "r") as f:
        return f.read()


def send_agent_request(semantic_model_file, prompt):
    analyst_endpoint = "/api/v2/cortex/agent:run"
    url = "https://" + os.getenv("SNOWFLAKE_HOST") + analyst_endpoint
    print(url)
    """Sends the prompt using the semantic model file """
    headers = {
        "Content-Type": "application/json",
        "accept": "application/json",
        "Authorization": f"Bearer {get_login_token()}",
        "X-Snowflake-Authorization-Token-Type": "OAUTH"
    }
    # Can be whatever; but it must conform with
    # https://docs.snowflake.com/en/user-guide/snowflake-cortex/cortex-agents-rest-api#sample-request
    request_body = {
        "model": "llama3.1-8b",
        "messages": [
            {
                "role": "user",
                "content": [{"type": "text", "text": prompt}],
            }
        ],
        "tools": [
            {
                "tool_spec": {
                    "type": "cortex_analyst_text_to_sql",
                    "name": "Analyst1",
                },
            }
        ],
        "tool_resources": {
            "Analyst1": {
                "semantic_model_file": semantic_model_file,
            },
        },
    }

    return requests.post(url, headers=headers, data=json.dumps(request_body))


def parse_sse_response(text):
    """Parse SSE response text to extract the complete message."""

    # Extract all text parts from the SSE stream
    message_parts = []

    # Split by lines and look for data lines
    lines = text.split('\n')

    for line in lines:
        if line.startswith('data: ') and 'delta' in line:
            # Remove 'data: ' prefix
            json_str = line[6:]

            try:
                data = json.loads(json_str)
                if 'delta' in data and 'content' in data['delta']:
                    for content in data['delta']['content']:
                        if content.get('type') == 'text' and 'text' in content:
                            message_parts.append(content['text'])
            except json.JSONDecodeError:
                continue

    # Combine all text parts
    complete_message = ''.join(message_parts)

    # Parse the JSON content from the assembled message
    if complete_message:
        try:
            # We need to extract just the JSON part
            json_start = complete_message.find('{')
            if json_start != -1:
                json_str = complete_message[json_start:]
                questions_data = json.loads(json_str)
                return questions_data
        except json.JSONDecodeError as e:
            return {"questions": complete_message, "error": str(e)}

    return {"questions": complete_message}

