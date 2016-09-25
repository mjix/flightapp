# flightapp
flightapp create from flightphp(git: [https://github.com/mikecao/flight])

#Installation
If you're using Composer, you can run the following command:
```
composer update
```

#config
```php
//set config
config(['key'=>'name']);

//get config; second argument is default config value
config('key', $defaultconfig);

//get config from config/file.php
config('app.env', 'local'); /read from config/app.php
```

#HTTP Requests
```php
//get post data
$val = input('post_key', $defaultval);
$allPostData = request()->data;

//get query parameters
$val = query('get_key', $defaultval);
$allQuery = request()->query;

//get from $_REQUEST
$val = all('key', $defaultval);
$all = request()->all;
```

#DB
orm create from [https://github.com/mardix/VoodOrm]; config in config/databases.php

##query
```php
//get rowlist
$table = db('testdb')->table('news');
$list = $table->get();

//return pagination 
// ['total' => 1, 'per_page' => 1, 'current_page' => 1, 
//  'from' => 0, 'to' => 1, 'data' => $rowlist]
$pageData = $table->paginate();

//get id=1 row
$first = $table->where('id', 1)->get();
```

##update
```php
$table = db('testdb')->table('news');
$table->where('id', 1)->update(['name'=>'username']);
```

##insert
```php
$table = db('testdb')->table('news');
$table->insert(['name'=>'username']);
```

##delete
```php
$table = db('testdb')->table('news');
$table->where('id', 1)->delete();
```

#facades
```
app();                                  //fligtphp instance Flight::app();
request();                              //add request()->all; Flight::request();
view('dirname/filename', assignData);   //render template

db($confname);                          //get orm
url($subpath);                          //get full url
config();                               //config
json();                                 //echo json string
all();                                  //input(); query();
url_origin();                           //eg: http://www.domainname.com
resource_path('/config/databases.php'); //get full path file
```

