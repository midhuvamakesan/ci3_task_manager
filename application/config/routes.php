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
|	$route['default_controller'] = 'welcome';
|
| This route indicates which controller class should be loaded if the
| URI contains no data. In the above example, the "welcome" class
| would be loaded.
|
|	$route['404_override'] = 'errors/page_missing';
|
| This route will tell the Router which controller/method to use if those
| provided in the URL cannot be matched to a valid route.
|
|	$route['translate_uri_dashes'] = FALSE;
|
| This is not exactly a route, but allows you to automatically route
| controller and method names that contain dashes. '-' isn't a valid
| class or method name character, so it requires translation.
| When you set this option to TRUE, it will replace ALL dashes in the
| controller and method URI segments.
|
| Examples:	my-controller/index	-> my_controller/index
|		my-controller/my-method	-> my_controller/my_method
*/
/*$route['default_controller'] = 'frontend';
$route['404_override'] = '';
$route['translate_uri_dashes'] = FALSE; */


/*$route['default_controller'] = 'frontend'; 



$route['tasks']['GET'] = 'api/tasks/index';
$route['tasks']['POST'] = 'api/tasks/store';
$route['tasks/(:num)']['GET'] = 'api/tasks/show/$1';
$route['tasks/(:num)']['PUT'] = 'api/tasks/update/$1';
$route['tasks/(:num)']['DELETE'] = 'api/tasks/delete/$1';
$route['tasks/(:num)/restore']['PATCH'] = 'api/tasks/restore/$1';
$route['tasks/(:num)/toggle-status']['PATCH'] = 'api/tasks/toggle_status/$1';


// accept PATCH or POST
$route['tasks/(:num)/restore']              = 'api/tasks/restore/$1';  


$route['tasks/(:num)/toggle-status'] = 'tasks/toggle_status/$1';
$route['tasks/(:num)'] = 'tasks/show/$1';
$route['tasks'] = 'tasks/index';




// Tags API
$route['api/tags'] = 'api/tags/index';
$route['api/tags/(:num)'] = 'api/tags/show/$1';
$route['api/tags/store'] = 'api/tags/store';
$route['api/tags/update/(:num)'] = 'api/tags/update/$1';
$route['api/tags/delete/(:num)'] = 'api/tags/delete/$1';*/


$route['default_controller'] = 'frontend';

// Frontend pages
$route['tasks'] = 'frontend/tasks'; // Only for rendering HTML page
$route['tasks/(:any)'] = 'frontend/tasks/$1'; // optional for other pages

// --- API Routes ---
$route['api/tasks']['GET']    = 'api/tasks/index';
$route['api/tasks']['POST']   = 'api/tasks/store';
$route['api/tasks/(:num)']['GET']    = 'api/tasks/show/$1';
$route['api/tasks/(:num)']['PUT']    = 'api/tasks/update/$1';
$route['api/tasks/(:num)']['DELETE'] = 'api/tasks/delete/$1';
$route['api/tasks/(:num)/restore']           = 'api/tasks/restore/$1';
$route['api/tasks/(:num)/toggle-status']     = 'api/tasks/toggle_status/$1';

// Tags API
$route['api/tags']                = 'api/tags/index';
$route['api/tags/(:num)']         = 'api/tags/show/$1';
$route['api/tags/store']          = 'api/tags/store';
$route['api/tags/update/(:num)'] = 'api/tags/update/$1';
$route['api/tags/delete/(:num)'] = 'api/tags/delete/$1';







