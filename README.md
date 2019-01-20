LaraSpells - Laravel CRUD Generator for 'Sorcerers'
========================================================

LaraSpell is Laravel CRUD generator that generate laravel code. 
So you can modify generated files as you need, without limitation.
And all you need to customize it is just some PHP and Laravel knowledges.

## Demo (Video)

* https://www.facebook.com/em.sifa/videos/2073212012693128/
* https://www.facebook.com/em.sifa/videos/2180691341945194/
* https://www.facebook.com/em.sifa/posts/2212262218788106

## Features

* Limitless Customization
* Repository Pattern
* Custom Template

## Requirements

* php >= 5.6
* composer
* Laravel 5.4

## Installation

Run composer command below to install `laraspells/generator`:

```
composer require "laraspells/generator"
```

Now open `config/app.php`, add `LaraSpells\Generator\LaraSpellServiceProvider::class` to 'providers' section.

## Quickstart

#### Make a schema

Run this command to make a schema:

```
php artisan spell:make admin
```

This command will generate `admin.yml` into your project directory.

#### Modify schema

Open `admin.yml`, modify as you needs. Look at [schema](#schema) section for more detail about schema. 

#### Generate It

Run this command

```
php artisan spell:generate admin
```

Now you can use it by adding generated service provider to your `config/app.php` file.

## Schema

#### Structure

```yml
---
name: Schema Name                                   # Schema name (for now it's just for information, not used to generate anything)
template: vendor/laraspells/generator/template       # Template directory
author: 
  name: Author Name                                 # Author name (used for generated PHP classes)
  email: author@email.example                       # Author email
config_file: admin                                  # Config file to store 'menu' and 'repositories' configuration
upload_disk: uploads                                # Upload disk to store uploaded files
provider:
  file: app/Providers/AdminServiceProvider.php      # Provider class filepath 
  class: App\Providers\AdminServiceProvider         # Provider class name
controller:
  path: app/Http/Controllers                        # Path to store generated controller files
  namespace: App\Http\Controllers                   # Controller class namespace 
request:
  path: app/Http/Requests                           # Path to store generated requests files
  namespace: App\Http\Requests                      # Requests class namespace
model:
  path: app/Models                                  # Path to store model files
  namespace: App\Models                             # Model class namespace 
repository:
  path: app/Repositories                            # Path to store repository files
  class: App\Repositories                           # Repository class/interface namespace
view:
  path: resources/views                             # Path to store view files
  namespace: ""                                     # View namespace
route:
  file: routes/web.php                              # Path to routes file
  name: admin::                                     # Route group name
  prefix: admin                                     # Route prefix
tables:                                             # - List tables -
  todos:                                            # Table 'todos' schema
    plural: todos                                   # [optional] Plural name
    singular: todo                                  # [optional] Singular name
    label: Todo                                     # Crud label
    icon: fa-list                                   # Icon class
    fields:                                         # List fields
      title:                                        # Field 'title' schema
        type: string                                # Field type (for migration)
        label: Title                                # Field label
        length: 80                                  # [optional] Field length
        input: text                                 # Field input type (string|assoc)
        rules:                                      # Field rules
        - required
      description:
        type: text
        label: Description
        input: textarea
        rules:
        - required
      status:
        type: enum:waiting,progress,done            # Field type with parameters
        label: Status
        input:                                      # Field input type using assoc (with input parameters)
          type: radios
          options:
            waiting: Waiting
            progress: On Progress
            done: Done
        rules:
        - required
        - in:waiting,progress,done
```

