<?php
$string['fetchbutton'] = 'Request Data';
$string['hackaton_intro'] = 'This is a page of a Hackaton plugin that calls an external API and displays the data.';

$string['pluginname'] = 'DA Courses';
$string['navmenu'] = 'Hack4aCase plugin';
$string['navtitle'] = 'DA Courses Admin';
$string['norecords'] = 'No records found';
$string['cap:view'] = 'View DA Courses page';
$string['cap:manage'] = 'Manage DA Courses';

$string['form:heading'] = 'Create course with PDF';
$string['form:coursename'] = 'Course name';
$string['form:file'] = 'PDF file';
$string['form:submit'] = 'Save';

$string['settings:heading'] = 'DA Courses settings';
$string['settings:endpoint'] = 'External endpoint URL';
$string['settings:endpoint_desc'] = 'A URL to POST the uploaded PDF to (multipart/form-data).';
$string['settings:apikey'] = 'API key (optional)';
$string['settings:apikey_desc'] = 'Sent as header "X-API-Key: ..." if provided.';

$string['msg:stored'] = 'Course saved and file uploaded successfully.';
$string['msg:curlsent'] = 'PDF posted to external endpoint.';
$string['err:nopdf'] = 'Please upload a PDF file.';
$string['err:endpoint_missing'] = 'Saved locally, but endpoint URL is not configured.';
$string['err:curl'] = 'Saved locally, but sending to external endpoint failed: {$a}';
