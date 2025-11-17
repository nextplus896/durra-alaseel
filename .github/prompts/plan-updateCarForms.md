## Plan: Update car data model and forms

TL;DR: Remove the “area” field from admin and vendor car forms, introduce `car_type` and `car_model` (with image per model), and convert vendor vehicle-model input into a cascading dropdown (car type → car model). Changes include DB migrations for `car_types`/`car_models`, new Eloquent models, small controller/API additions for AJAX model lookup, form/view edits, and request validation updates.

### Steps

1. Add DB tables: create migrations `database/migrations/*_create_car_types_table.php` and `*_create_car_models_table.php` and models `app/Models/CarType.php`, `app/Models/CarModel.php`.
2. Update `app/Models/Car.php` to reference `car_type_id` and `car_model_id` (add relations).
3. Update admin UI: edit `routes/admin.php`, `app/Http/Controllers/Admin/CarController.php`, and views `resources/views/admin/cars/index.blade.php` and `resources/views/admin/cars/form.blade.php` to remove area, add car type/model columns and form fields.
4. Update vendor forms: edit `routes/vendor.php`, `app/Http/Controllers/Vendor/CarController.php` (or equivalent), and `resources/views/vendor/cars/create.blade.php` / `.../edit.blade.php` to remove area and fields `experience`, `vehicle_number`, `per_km_charge`, and change vehicle model input to a dropdown that cascades with selected car type.
5. Add AJAX route and controller method (e.g., `GET /api/car-models?type_id=`) in `routes/api.php` or `routes/vendor.php` and implement data return in `app/Http/Controllers/Api/CarModelController.php`.
6. Frontend: add small JS in `resources/js/` (or inline) to fetch models when car type changes, populate model dropdown, and display model image thumbnail; ensure upload/display of `car_model` images stored in `storage/app/public` and referenced via `Storage::url()`.
7. Update form requests/validation: remove `area`, `experience`, `vehicle_number`, `per_km_charge` from `app/Http/Requests/*Car*Request.php` and add validation for `car_type_id` and `car_model_id`.
8. Add seeder `database/seeders/CarTypesSeeder.php` and `CarModelsSeeder.php` for initial data and optional admin UI to manage types/models.

### Further Considerations

1. Data migration: decide whether to keep existing `area` column (backfill, archive, or drop). Option A: keep column and mark deprecated. Option B: migrate area relations to new structure then drop.
2. Admin management: add simple CRUD for `car_type` and `car_model` in admin (`app/Http/Controllers/Admin/CarTypeController.php`, views under `resources/views/admin/car-types/*`) so non-devs can add models and upload images.
3. API & permissions: secure AJAX model-endpoint with existing auth middleware and add caching if many models.

Notes/assumptions/questions

-   I will need to inspect exact files for controllers, requests, and blade templates to provide precise file edits and code snippets. Which admin/vendor controller class names and view file paths do you prefer I target, or should I locate them in the repo first?
-   Confirm whether `car_model.image` should be a single image path (string) or support multiple images. I assumed a single `image` column per model.
