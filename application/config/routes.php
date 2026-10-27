<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
| -------------------------------------------------------------------------
| URI ROUTING
| -------------------------------------------------------------------------
| This file lets you re-map URI requests to specific controller functions.
|
| Typically there is a one-to-one relationship between a URL string
| and its corresponding controller class/method. The segments in a
| URL normally follow this pattern:
|
|	example.com/class/method/id/
|
| In some instances, however, you may want to remap this relationship
| so that a different class/function is called than the one
| corresponding to the URL.
|
| Please see the user guide for complete details:
|
|	https://codeigniter.com/userguide3/general/routing.html
|
| -------------------------------------------------------------------------
| RESERVED ROUTES
| -------------------------------------------------------------------------
|
| There are three reserved routes:
|
*/

$route['default_controller'] = 'home';
$route['404_override'] = '';
$route['translate_uri_dashes'] = FALSE;

// ---- Publik & autentikasi
$route['login']['get']              = 'auth/login';
$route['login']['post']             = 'auth/authenticate';
$route['logout']['post']            = 'auth/logout';
$route['ganti-sandi']['get']        = 'account/password';
$route['ganti-sandi']['post']       = 'account/update_password';
$route['dashboard']                 = 'dashboard';
$route['admin-lomba']['get']                         = 'manager_dashboard/index';
$route['admin-lomba/nilai']['get']                   = 'manager_dashboard/index';
$route['admin-lomba/nilai/([a-z0-9-]+)']['get']      = 'manager_dashboard/competition/$1';
$route['admin-lomba/nilai/([a-z0-9-]+)/(\d+)/(\d+)']['post'] = 'manager_dashboard/save/$1/$2/$3';
$route['karya']['post']             = 'home/submit_work';
$route['denah']['get']              = 'home/site_plan';
$route['juklak']['get']             = 'home/guidelines';
$route['bazaar/umkm']['post']       = 'home/submit_bazaar/umkm';
$route['siaran/cerdas-cermat/([a-z0-9-]+)/feed']['get'] = 'public_scoreboard/feed/$1';
$route['siaran/cerdas-cermat/([a-z0-9-]+)']['get'] = 'public_scoreboard/show/$1';

// ---- Admin Desa (/desa)
$route['desa']                                  = 'desa/desa_dashboard';
$route['desa/bazaar']['get']                    = 'desa/desa_bazaar/index';
$route['desa/bazaar']['post']                   = 'desa/desa_bazaar/save';
$route['desa/kontrol-lomba']                    = 'desa/desa_kontrol/lomba';
$route['desa/kontrol-nilai']                    = 'desa/desa_kontrol/nilai';
$route['desa/daftar']['get']                    = 'desa/desa_daftar/index';
$route['desa/daftar/([a-z0-9-]+)']['post']      = 'desa/desa_daftar/save/$1';
$route['desa/peserta']['get']                   = 'desa/desa_peserta/index';
$route['desa/peserta/(\d+)/ubah']['get']        = 'desa/desa_peserta/edit_open/$1';
$route['desa/peserta/(\d+)/ubah']['post']       = 'desa/desa_peserta/save_open/$1';
$route['desa/peserta/(\d+)/foto']['post']       = 'desa/desa_peserta/upload_photo/$1';
$route['desa/peserta/(\d+)/foto']['get']        = 'desa/desa_peserta/photo/$1';
$route['desa/hasil']                            = 'desa/desa_hasil';
$route['desa/karya']['get']                     = 'desa/desa_karya/index';
$route['desa/karya/(\d+)']['post']              = 'desa/desa_karya/update/$1';

// ---- Juri (/juri)
$route['juri']                                  = 'juri/juri_dashboard';
$route['juri/kontrol-lomba']                    = 'juri/juri_kontrol/lomba';
$route['juri/kontrol-nilai']                    = 'juri/juri_kontrol/nilai';
$route['juri/lomba']                            = 'juri/juri_lomba/index';
$route['juri/penilaian']                        = 'juri/juri_lomba/penilaian';
$route['juri/riwayat']                          = 'juri/juri_lomba/riwayat';
$route['juri/nilai/([a-z0-9-]+)/submit']['post'] = 'juri/juri_lomba/submit/$1';
$route['juri/nilai/([a-z0-9-]+)/babak/([1-4])/entry/(\d+)/poin']['post'] = 'juri/juri_lomba/cerdas_points/$1/$2/$3';
$route['juri/nilai/([a-z0-9-]+)/babak/([1-4])/entry/(\d+)/penalti']['post'] = 'juri/juri_lomba/cerdas_penalty/$1/$2/$3';
$route['juri/nilai/([a-z0-9-]+)/babak/([1-4])/lanjut']['post'] = 'juri/juri_lomba/advance_cerdas_cermat/$1/$2';
$route['juri/nilai/([a-z0-9-]+)/babak/([1-4])/kembali']['post'] = 'juri/juri_lomba/back_cerdas_cermat/$1/$2';
$route['juri/nilai/([a-z0-9-]+)']['get']        = 'juri/juri_lomba/nilai/$1';
$route['juri/nilai/([a-z0-9-]+)/(\d+)/foto/(\d+)']['get'] = 'juri/juri_lomba/photo/$1/$2/$3';
$route['juri/nilai/([a-z0-9-]+)/(\d+)']['get']  = 'juri/juri_lomba/entry/$1/$2';
$route['juri/nilai/([a-z0-9-]+)/(\d+)']['post'] = 'juri/juri_lomba/save/$1/$2';

// ---- Panitia (/admin)
$route['admin']                                 = 'admin/admin_dashboard';
$route['admin/feed']['get']                     = 'admin/admin_dashboard/feed';
$route['admin/kontrol-lomba']                   = 'admin/admin_kontrol/lomba';
$route['admin/kontrol-nilai']                   = 'admin/admin_kontrol/nilai';
$route['admin/desa']['get']                     = 'admin/admin_desa/index';
$route['admin/desa/simpan']['post']             = 'admin/admin_desa/save';
$route['admin/desa/(\d+)/hapus']['post']        = 'admin/admin_desa/delete/$1';
$route['admin/lomba']['get']                    = 'admin/admin_lomba/index';
$route['admin/lomba/simpan']['post']            = 'admin/admin_lomba/save';
$route['admin/lomba/(\d+)/pendaftaran']['post']  = 'admin/admin_lomba/registration/$1';
$route['admin/lomba/(\d+)/hapus']['post']       = 'admin/admin_lomba/delete/$1';
$route['admin/pendaftaran']['get']              = 'admin/admin_pendaftaran/index';
$route['admin/peserta']['get']                  = 'admin/admin_pendaftaran/index';
$route['admin/pendaftaran/export']              = 'admin/admin_pendaftaran/export';
$route['admin/peserta/export']                  = 'admin/admin_pendaftaran/export';
$route['admin/pendaftaran/status/(\d+)']['post'] = 'admin/admin_pendaftaran/status/$1';
$route['admin/absensi']['get']                    = 'admin/admin_absensi/index';
$route['admin/absensi/(\d+)']['post']             = 'admin/admin_absensi/mark/$1';
$route['admin/absensi/foto/(\d+)']['get']         = 'admin/admin_absensi/photo/$1';
$route['admin/bazaar']['get']                     = 'admin/admin_bazaar/index';
$route['admin/bazaar/umkm/pendaftaran']['post']   = 'admin/admin_bazaar/umkm_registration';
$route['admin/buzzer']['get']                     = 'admin/admin_buzzer/index';
$route['admin/hasil']['get']                    = 'admin/admin_hasil/index';
$route['admin/hasil/export']                    = 'admin/admin_hasil/export';
$route['admin/hasil/([a-z0-9-]+)']['get']       = 'admin/admin_hasil/detail/$1';
$route['admin/hasil/koreksi']['post']           = 'admin/admin_hasil/edit_scores';
$route['admin/hasil/reset']['post']             = 'admin/admin_hasil/reset_judge';
$route['admin/pemenang']['get']                 = 'admin/admin_pemenang/index';
$route['admin/pemenang/(\d+)/publikasi']['post'] = 'admin/admin_pemenang/publish/$1';
$route['admin/pemenang/penalti']['post']        = 'admin/admin_pemenang/penalty_add';
$route['admin/pemenang/penalti/(\d+)/hapus']['post'] = 'admin/admin_pemenang/penalty_delete/$1';
$route['admin/homepage']['get']                 = 'admin/admin_homepage/index';
$route['admin/homepage/konten']['post']         = 'admin/admin_homepage/content';
$route['admin/homepage/acara']['post']          = 'admin/admin_homepage/event';
$route['admin/homepage/rundown']['post']        = 'admin/admin_homepage/rundown';
$route['admin/homepage/denah']['post']          = 'admin/admin_homepage/site_plan';
$route['admin/pengumuman']['get']               = 'admin/admin_pengumuman/index';
$route['admin/pengumuman']['post']              = 'admin/admin_pengumuman/save';
$route['admin/akun']['get']                     = 'admin/admin_akun/index';
$route['admin/akun/buat']['post']               = 'admin/admin_akun/create';
$route['admin/akun/(\d+)/ubah']['post']         = 'admin/admin_akun/update/$1';
$route['admin/akun/(\d+)/juri']['post']         = 'admin/admin_akun/assign/$1';
$route['admin/akun/(\d+)/penugasan']['post']   = 'admin/admin_akun/assign/$1';
$route['admin/akun/(\d+)/sandi']['post']        = 'admin/admin_akun/reset_password/$1';
$route['admin/akun/(\d+)/hapus']['post']        = 'admin/admin_akun/delete/$1';
