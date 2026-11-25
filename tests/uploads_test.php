<?php
// Picture uploads: every refusal comes with a specific reason (save_image_error()).
require __DIR__ . '/bootstrap.php';

$tmp = sys_get_temp_dir();
$png = $tmp . '/aku_test.png';  file_put_contents($png, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg=='));
$txt = $tmp . '/aku_test.txt';  file_put_contents($txt, 'not a picture');

section('No file chosen');
checkTest('Nothing uploaded returns null (keep current picture)', save_image(['name' => '', 'error' => UPLOAD_ERR_NO_FILE], 'products'), null);

section('Refusals say why');
checkTest('Too big for the server', save_image(['name' => 'a.jpg', 'error' => UPLOAD_ERR_INI_SIZE], 'products'), false);
checkTest('  ...message mentions the server limit', strpos(save_image_error(), 'too large for the server') !== false, true);
checkTest('Over the site limit', save_image(['name' => 'a.png', 'error' => UPLOAD_ERR_OK, 'size' => 9 * 1048576, 'tmp_name' => $png], 'products'), false);
checkTest('  ...message gives the size', strpos(save_image_error(), '9 MB') !== false, true);
checkTest('Interrupted upload', save_image(['name' => 'a.png', 'error' => UPLOAD_ERR_PARTIAL], 'products'), false);
checkTest('  ...message says try again', strpos(save_image_error(), 'did not upload completely') !== false, true);
checkTest('Text file pretending to be a picture', save_image(['name' => 'a.jpg', 'error' => UPLOAD_ERR_OK, 'size' => 13, 'tmp_name' => $txt], 'products'), false);
checkTest('  ...message asks for JPG/PNG/WebP', strpos(save_image_error(), 'JPG, PNG or WebP') !== false, true);
checkTest('iPhone HEIC photo', save_image(['name' => 'IMG_0001.HEIC', 'error' => UPLOAD_ERR_OK, 'size' => 13, 'tmp_name' => $txt], 'products'), false);
checkTest('  ...message explains HEIC', strpos(save_image_error(), 'HEIC') !== false, true);

section('Real pictures are recognised');
checkTest('A real PNG is recognised as a picture', (getimagesize($png)['mime'] ?? ''), 'image/png');

@unlink($png); @unlink($txt);
finish();
