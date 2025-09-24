# 🚀 DataArt Course Q\&A Generator — Moodle Plugin

Turn **course PDFs** into **questions & answers** via **Snowflake (stage + storage table) and Snowflake Cortex**, then store the generated Q\&A back in Moodle and link them to the correct course. Includes an editor and selective re-generation.

---

## Table of Contents

* [Overview](#overview)
* [Architecture & Data Flow](#architecture--data-flow)
* [Prerequisites](#prerequisites)
* [Installation](#installation)
* [Configuration](#configuration)
* [Using the Plugin](#using-the-plugin)

  * [Upload & Generation Workflow](#upload--generation-workflow)
  * [Editing, Approving, Regenerating](#editing-approving-regenerating)
* [Backend Endpoint Contract](#backend-endpoint-contract)

  * [Request](#request)
  * [Response](#response)


---

## Overview

This plugin lets you:

* Upload a **PDF course file** and select/enter a **course**.
* Send the file to a **backend** (`http://localhost:5000/upload`) that:

  * Parses the PDF in Python.
  * Stores raw/parsed content to a **Snowflake stage** and a **Snowflake storage table**, linked by **courseId**.
  * Sends parsed content to **Snowflake Cortex** to generate **questions & answers (Q\&A)**.
  * Returns the generated Q\&A to Moodle.
* Store the Q\&A in Moodle and attach them to the selected course.
* **Edit** Q\&A, **approve** final items, and **regenerate** only the **unapproved** ones.

---

## Architecture & Data Flow

```
[Moodle Plugin UI]
      |
      | 1) POST PDF + course info
      v
[Backend @ http://localhost:5000/upload]
      | 2) Parse PDF (Python)
      | 3) Stage raw/parsed to Snowflake stage
      | 4) Put parsed content -> Snowflake storage table (courseId)
      | 5) Send parsed content -> Snowflake Cortex (generate Q&A)
      v
[Backend returns Q&A JSON]
      |
      | 6) Store Q&A in Moodle + attach to course
      v
[Moodle Plugin UI: edit / approve / regenerate (unapproved only)]
```

---

## Prerequisites

* Moodle admin access to install and configure plugins.
* A locally running backend service at **`http://localhost:5000/upload`** that accepts `multipart/form-data` and implements the flow above.
* Snowflake:

  * A **stage** for file/content staging.
  * A **storage table** for parsed content (with `courseId` linkage).
  * Credentials for the backend to write to the stage & table.
  * **Snowflake Cortex** enabled and accessible from the backend.

---

## Installation

1. In Moodle, go to **Site administration → Plugins → Install plugins**.
2. Upload the plugin ZIP and complete the installation.
3. After installation, proceed to **Configuration**.

---

## Configuration

1. Go to **Site administration → Plugins → Course Q\&A Generator (this plugin)**.

2. Set **Backend Upload URL** to:

   ```
   http://localhost:5000/upload
   ```

3. Save changes.

> The backend must be reachable from the Moodle server and accept `multipart/form-data` with the PDF and course metadata.

---

## Using the Plugin

### Upload & Generation Workflow

1. Open **Site administration → Plugins → Course Q\&A Generator** (plugin main page).
2. Use the **Upload form**:

   * **Course**: select an existing course (or enter the course name if the UI supports it).
   * **PDF file**: choose the course PDF.
3. Click **Upload**.
4. The plugin sends the file + course info to the backend:

   * Backend **parses** the PDF.
   * Stores data to Snowflake **stage** and **storage table** (linked to **courseId**).
   * Calls **Snowflake Cortex** to generate **Q\&A**.
   * Returns the **Q\&A JSON** to Moodle.
5. The plugin **stores Q\&A** in Moodle and **attaches** them to the selected course.

### Editing, Approving, Regenerating

On the plugin page (or per-course Q\&A view), you can:

* **Edit** question text, answers, explanations; **Save** changes.
* **Approve** finalized items. Approved items are **not regenerated**.
* **Regenerate** Q\&A — this only affects **unapproved** items.

**Regeneration rule:**

> Only **unapproved** questions/answers are regenerated. Approved items remain as-is (but can still be manually edited).

---

## Backend Endpoint Contract

> The plugin expects a single endpoint that handles upload, parsing, Snowflake ops, Cortex generation, and returns Q\&A.

### Request

* **Method:** `POST`
* **URL:** `http://localhost:5000/upload`
* **Content-Type:** `multipart/form-data`
* **Fields:**

  * `file` — PDF file
  * `courseId` — Moodle course ID (string/integer)


### Response

* **200 OK** with JSON payload similar to:

```json
{
  "status": "ready",
  "courseId": "12345",
  "questions": [
    {
      "id": "q-001",
      "question": "What is ...?",
      "answers": [
        {"text": "Option A", "correct": false},
        {"text": "Option B", "correct": true},
        {"text": "Option C", "correct": false}
      ],
      "explanation": "Because ...",
      "approved": false
    }
  ]
}
```

* On error, respond with non-200 and a JSON error body:

```json
{
  "status": "error",
  "message": "Parsing failed: <details>"
}
```

---



## 🚀 Plugin Local Python Cortex

To have functionality to execute python code, we have added a new container which will be a flask app with storage mounted to it. If someone wants to execute python they will upload their .py files to the stage that is mounted to the container running flask, and this can be done using the Snowflake Snowsite. The moodle php plug-in will be able to send a request to the flask application containing the name of the .py to execute and any arguments if required. The flask app will receive it and download the .py file and use the exec command and run the code. The output will be returned to the php plugin and can be formatted on the php side to display to the user.

![Screenshot](./instruction_images/image1.png)

If you have had an instance of moodle running before in your Snowflake environment, go to Snowsite UI and run the following commands in a worksheet. If not, skip this step.

```
USE ROLE MOODLE_ROLE;

DROP Service MOODLE_APP.PUBLIC.MOODLE_SERVICE;

DROP compute pool moodle_compute_pool;

DROP IMAGE REPOSITORy MOODLE_APP.PUBLIC.IMG;
```

## Open a terminal and navigate to the directory [Moodle](./Moodle/).

![Screenshot](./instruction_images/image2.png)


## Use the data files in this folder: Data Files

Ensure that you have the “moodle_role” role selected

## Navigate to the Snowflake snowsight and on the left navigation bar hover over the “Data” tab (has a database icon) and click on “add data”

![Screenshot](./instruction_images/image3.png)

Click on “Load files into a stage” and fill in the fields as below and upload (NOTE: Ensure that you specify the path to a folder correctly)

![Screenshot](./instruction_images/image4.png)

Run the following commands in Snowflake Snowsight

```
USE ROLE SECURITYADMIN;

GRANT DATABASE ROLE SNOWFLAKE.CORTEX_USER TO ROLE moodle_role;

USE ROLE MOODLE_ROLE;

USE DATABASE moodle_app;

-- Fact table: daily_revenue

CREATE OR REPLACE TABLE moodle_app.public.daily_revenue (

date DATE,

revenue FLOAT,

cogs FLOAT,

forecasted_revenue FLOAT,

product_id INT,

region_id INT

);

-- Dimension table: product_dim

CREATE OR REPLACE TABLE moodle_app.public.product_dim (

product_id INT,

product_line VARCHAR(16777216)

);

-- Dimension table: region_dim

CREATE OR REPLACE TABLE moodle_app.public.region_dim (

region_id INT,

sales_region VARCHAR(16777216),

state VARCHAR(16777216)

);

COPY INTO moodle_app.public.DAILY_REVENUE

FROM @MOODLE_APP.PUBLIC.MOUNTED

FILES = ('moodledata/daily_revenue.csv')

FILE_FORMAT = (

TYPE=CSV,

SKIP_HEADER=1,

FIELD_DELIMITER=',',

TRIM_SPACE=FALSE,

FIELD_OPTIONALLY_ENCLOSED_BY=NONE,

REPLACE_INVALID_CHARACTERS=TRUE,

DATE_FORMAT=AUTO,

TIME_FORMAT=AUTO,

TIMESTAMP_FORMAT=AUTO

EMPTY_FIELD_AS_NULL = FALSE

error_on_column_count_mismatch=false

)

ON_ERROR=CONTINUE
```
![Screenshot](./instruction_images//image5.png)
```
FORCE = TRUE ;

COPY INTO moodle_app.public.PRODUCT_DIM

FROM @MOODLE_APP.PUBLIC.MOUNTED

FILES = ('moodledata/product.csv')

FILE_FORMAT = (

TYPE=CSV,

SKIP_HEADER=1,

FIELD_DELIMITER=',',

TRIM_SPACE=FALSE,

FIELD_OPTIONALLY_ENCLOSED_BY=NONE,

REPLACE_INVALID_CHARACTERS=TRUE,

DATE_FORMAT=AUTO,

TIME_FORMAT=AUTO,

TIMESTAMP_FORMAT=AUTO

EMPTY_FIELD_AS_NULL = FALSE

error_on_column_count_mismatch=false

)

ON_ERROR=CONTINUE
```
![Screenshot](./instruction_images/image6.png)
```
FORCE = TRUE ;

COPY INTO moodle_app.public.REGION_DIM

FROM @MOODLE_APP.PUBLIC.MOUNTED

FILES = ('moodledata/region.csv')

FILE_FORMAT = (

TYPE=CSV,

SKIP_HEADER=1,

FIELD_DELIMITER=',',

TRIM_SPACE=FALSE,

FIELD_OPTIONALLY_ENCLOSED_BY=NONE,

REPLACE_INVALID_CHARACTERS=TRUE,

DATE_FORMAT=AUTO,

TIME_FORMAT=AUTO,

TIMESTAMP_FORMAT=AUTO

EMPTY_FIELD_AS_NULL = FALSE

error_on_column_count_mismatch=false

)

ON_ERROR=CONTINUE
```
![Screenshot](./instruction_images/image7.png)
```
FORCE = TRUE ;
```

## Open directory [cortex](./cortex/), zip the content of this folder as *** cortex.zip *** and navigate to the “Site Administration” tab on the top left on your moodle instance.

![Screenshot](./instruction_images/image8.png)

## Navigate to “Plugins”

![Screenshot](./instruction_images/image9.png)

Go to “Install Plugins” and upload your zip file.

Click on “Install plugin from the ZIP file”

Click “Continue” when the validation message shows up and “Continue” again. Press “Upgrade Moodle database now” to install the plugin. A success message will pop up if the installation is successful and the plugin is ready to use.

## Download the cortex.py file in the “python files” folder.

## Navigate to the Snowflake snowsight and on the left navigation bar hover over the “Data” tab (has a database icon) and click on “add data”

Click on “Load files into a stage” and fill in the fields as below and upload

## Navigate to /local/cortex/index.php to see the plugin

You can enter the name of your .py file and a question for cortex using the plug in and it will return the answer