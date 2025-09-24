<?php
require(__DIR__ . '/../../config.php');

require_login();
$context = context_system::instance();
require_capability('local/da_courses:manage', $context);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/da_courses/index.php'));
$PAGE->set_title(get_string('pluginname', 'local_da_courses'));
$PAGE->set_heading(get_string('navtitle', 'local_da_courses'));
$PAGE->requires->css(new moodle_url('/local/da_courses/styles/styles.css'));

echo $OUTPUT->header();

// Ensure draft helpers are available.
require_once($CFG->libdir . '/filelib.php');


function create_course_q_a($courseid, $data, $test = false) {
    global $DB;
    if ($test) {
        // Create some sample Q&A entries for this course.
        $data = [
            ['question' => 'What is Moodle?', 'answer' => 'Moodle is a free, open-source learning management system (LMS) used by educators to create online courses and websites.'],
            ['question' => 'Who developed Moodle?', 'answer' => 'Moodle was developed by Martin Dougiamas in 2002.'],
            ['question' => 'What programming language is Moodle written in?', 'answer' => 'Moodle is primarily written in PHP.'],
            ['question' => 'Is Moodle free to use?', 'answer' => 'Yes, Moodle is released under the GNU General Public License, making it free to download, use, and modify.'],
            ['question' => 'Can Moodle be customized?', 'answer' => 'Yes, Moodle is highly customizable with a wide range of plugins and themes available to extend its functionality and appearance.'],
        ];
    }
    else {
        if (!is_array($data) || empty($data)) {
            return;
        }
    }
    foreach ($data as $item) {
        $record = (object)[
            'course_id'    => $courseid,
            'q_text'    => $item['question'],
            'a_text'    => $item['answer'],
            'timecreated' => time(),
        ];
        $DB->insert_record('da_courses_questions', $record);
    }
}

$mform = new \local_da_courses\form\upload_form();

if ($mform->is_cancelled()) {
    redirect(new moodle_url('/admin/search.php'));
} else if ($data = $mform->get_data()) {
    global $DB, $CFG, $USER;
    // 1) Insert the DB row first to get $courseid (used as itemid in filearea).
    $record = (object)[
        'course_name' => $data->course_name,
        'file_id'     => null,
        'file_name'   => null,
        'file_path'   => null,
        'timecreated' => time(),
    ];
    $courseid = $DB->insert_record('da_courses', $record, true);

    // 2) Save draft to our plugin filearea (even if no file was chosen, this is harmless).
    $draftid = (int)($data->pdffile ?? 0);
    file_save_draft_area_files(
        $draftid,
        $context->id,
        'local_da_courses',
        'coursepdfs',
        $courseid,
        [
            'subdirs'        => 0,
            'maxbytes'       => $CFG->maxbytes,
            'maxfiles'       => 1,
            'accepted_types' => '*',    // <- no server-side type restriction
        ]
    );

    // 3) Try to read back whatever got saved.
    $fs     = get_file_storage();
    $files  = $fs->get_area_files($context->id, 'local_da_courses', 'coursepdfs', $courseid, 'id', false);
    if ($files) {
        /** @var stored_file $f */
        $f = reset($files);

        // Build a pluginfile URL (no download force).
        $fileurl = moodle_url::make_pluginfile_url(
            $context->id, 'local_da_courses', 'coursepdfs', $courseid, '/', $f->get_filename(), false
        )->out(false);

        // 4) Update DB with file metadata.
        $record->id  = $courseid;
        $record->file_id    = (string)$f->get_id();
        $record->file_name  = $f->get_filename();
        $record->file_path  = $fileurl;
        $DB->update_record('da_courses', $record);

        // 5) POST to external endpoint (skip type checks; just send).
        $endpoint = (string)get_config('local_da_courses', 'endpoint');
        $apikey   = (string)get_config('local_da_courses', 'apikey');

        if ($endpoint) {
            $tmpdir  = make_temp_directory('local_da_courses');
            $tmppath = $tmpdir . DIRECTORY_SEPARATOR . $f->get_contenthash();
            $f->copy_content_to($tmppath);

            $curl = curl_init();

            $postfields = [
                'course_id' => $record->id,
                'file_id' => $f->get_id(),
                'file'        => new \CURLFile($tmppath, 'application/octet-stream', $f->get_filename()),
            ];

            $flask_url = $endpoint;
            // Set cURL options
            curl_setopt_array($curl, array(
                CURLOPT_URL => $flask_url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $postfields,
                CURLOPT_HTTPHEADER => [
                    'Accept: application/json'
                ],
                CURLOPT_TIMEOUT => 90,
                CURLOPT_CONNECTTIMEOUT => 30
            ));

            // Execute the request
            $response = curl_exec($curl);
            $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            $curl_error = curl_error($curl);
            // Close cURL
            curl_close($curl);

            if ($curl_error) {
                \core\notification::warning('External POST failed: ' . $curl_error);
            } else {
                \core\notification::success(get_string('msg:curlsent', 'local_da_courses'));

                // Optionally decode JSON response.
                $decoded = json_decode($response, true);
                if (isset($decoded['quiz']) && $decoded['quiz'] && isset($decoded['quiz']['questions'])) {
                    $data = $decoded['quiz']['questions'];
                    // Save to DB
                    // Create sample Q&A entries for this course.
                    create_course_q_a($courseid, $data);
                }
            }

            @unlink($tmppath);
        } else {
            create_course_q_a($courseid, [], $test = true);
            \core\notification::warning(get_string('err:endpoint_missing', 'local_da_courses'));
        }
    } else {
        // No file saved — keep the DB row, just let the user know (no error).
        \core\notification::warning('No file was attached; saved course without file.');
    }

    \core\notification::success(get_string('msg:stored', 'local_da_courses'));
    redirect($PAGE->url);
}
else {
    // show "No records yet" message if no records in DB
    global $DB;
    if ($DB->count_records('da_courses') == 0) {
        echo $OUTPUT->notification(get_string('norecords', 'local_da_courses'), \core\output\notification::NOTIFY_INFO);
    }
    else {
        $sql = "
            SELECT
                c.id            AS courseid,
                c.course_name   AS coursename,
                c.file_id       AS fileid,
                c.file_name     AS filename,
                c.file_path     AS filepath,
                q.id            AS questionid,
                q.q_text        AS qtext,
                q.a_text        AS atext,
                q.isapproved    AS isapproved
            FROM {da_courses} c
            LEFT JOIN {da_courses_questions} q
                ON q.course_id = c.id
            ORDER BY c.id ASC, q.id ASC
        ";

        // Use a recordset (streaming, no key-collisions).
        $rs = $DB->get_recordset_sql($sql);

        $courses = []; // [courseid => (object){ id, coursename, questions: [...] }]

        // VERSION 2.0 ==============================
        foreach ($rs as $r) {
            $cid = (int)$r->courseid;
            if (!isset($courses[$cid])) {
                $courses[$cid] = (object)[
                    'id'         => $cid,
                    'coursename' => $r->coursename,
                    'filename'   => $r->filename,
                    'filepath'   => $r->filepath,
                    'fileid'     => $r->fileid,
                    'questions'  => [],
                ];
            }
            // If there is a question row, add it; LEFT JOIN means it can be NULL.
            if (!is_null($r->questionid)) {
                $courses[$cid]->questions[] = (object)[
                    'id'    => (int)$r->questionid,
                    'isapproved' => $r->isapproved,
                    'qtext' => (string)$r->qtext,
                    'atext' => (string)$r->atext,
                ];
            }
        }
        $rs->close();

        echo html_writer::start_div('da-courses-list');

        if (!$courses) {
            echo html_writer::div('No courses found.');
        } else {
            foreach ($courses as $course) {
                $filelink = $course->filepath ? html_writer::link($course->filepath, $course->filename) : '';
                echo html_writer::tag('h3',
                    s($course->coursename) . ' ( ID: ' . $course->id . ' File: ' . $filelink . ' )'
                );

                if (empty($course->questions)) {
                    echo html_writer::div('No questions for this course.', 'muted');
                    continue;
                }

                echo html_writer::start_tag('ol', ['class' => 'qa-list']);

                foreach ($course->questions as $qa) {
                    // <li data-qaid> is kept for the AMD module to find the row.
                    echo html_writer::start_tag('li', ['class' => 'qa-item', 'data-qaid' => $qa->id]);

                    // Fieldset wrapper.
                    echo html_writer::start_tag('fieldset', ['class' => 'qa-fieldset']);

                    // Collapsible container: <details> is collapsed by default when 'open' is omitted.
                    echo html_writer::start_tag('details', ['class' => 'qa-details', 'data-qaid' => $qa->id]);

                    // Header/legend-like summary line (click to expand/collapse).
                    $summary = html_writer::tag('strong', 'Q: ') . html_writer::tag('strong', s($qa->qtext), ['class' => 'qa-qsummary']);
                    echo html_writer::tag('summary', $summary, ['class' => 'qa-summary']);

                    // Body shown when expanded: answer + actions (Edit button for AMD).
                    echo html_writer::start_div('qa-body');

                    // Question text span kept (AMD replaces this with a textarea on Edit).
                    echo html_writer::div(
                        html_writer::tag('strong', 'Q: ') .
                        html_writer::tag('span', s($qa->qtext), ['class' => 'qa-qtext']),
                        'qa-q'
                    );

                    // Answer text span kept (AMD replaces this with a textarea on Edit).
                    echo html_writer::div(
                        html_writer::tag('strong', 'A: ') .
                        html_writer::tag('span', nl2br(s($qa->atext)), ['class' => 'qa-atext']),
                        'qa-a'
                    );

                    // Actions: Edit button stays at the right side.
                    $checkbox_attrs = ['data-qaid' => $qa->id, 'id' => 'approve_q_' . $qa->id, 'class' => 'qa-approve', 'type' => 'checkbox'];
                    if ((int)$qa->isapproved) { $checkbox_attrs['checked'] = 'checked'; }
                    echo html_writer::tag(
                        'label',
                        html_writer::tag('input', '', $checkbox_attrs) . ( (int)$qa->isapproved ? 'Approved' : 'Approve'),
                        ['for' => 'approve_q_' . $qa->id, 'class' => 'qa-approve']
                    );

                    echo html_writer::start_tag('div', ['class' => 'qa-actions']);
                    echo html_writer::tag('button', 'Edit', ['class' => 'btn btn-secondary qa-edit', 'style' => (int)$qa->isapproved ? 'display: none;' : '']);
                    echo html_writer::end_tag('div');

                    echo html_writer::end_div();      // .qa-body
                    echo html_writer::end_tag('details');
                    echo html_writer::end_tag('fieldset');
                    echo html_writer::end_tag('li');
                }
                echo html_writer::start_tag('li', ['class' => 'qa-item regenerate-wrapper']);
                echo html_writer::div(
                    html_writer::tag('button', 'Regenerate', ['data-courseid' => $course->id, 'data-fileid' => $course->fileid, 'class' => 'btn btn-primary qa-regenerate']) 
                    . html_writer::tag('button', 'Approve all', ['data-courseid' => $course->id, 'class' => 'btn btn-success qa-approve-all']),
                    'qa-all-actions'
                );
                echo html_writer::end_tag('li');
                echo html_writer::end_tag('ol');

            }
        }
        echo html_writer::end_div();

        // Load the AMD module once per page:
        $PAGE->requires->js_call_amd('local_da_courses/qasync', 'init', ['sesskey' => sesskey()]);
        $PAGE->requires->js_call_amd('local_da_courses/qaedit', 'init', ['sesskey' => sesskey()]);
    }
}

// Show the form.
$mform->display();

// (Optional) list last records... (unchanged)
echo $OUTPUT->footer();
