<?php

namespace App\Http\Controllers\Api\V1\User;

use Exception;
use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Helpers\Response;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class IdentificationController extends Controller
{
    /**
     * Get user's national ID and driving license images
     *
     * GET /api/v1/user/identification/info
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getIdentification()
    {
        try {
            $user = auth()->guard("api")->user();

            $response_data = [
                'national_id'      => $user->national_id ?? null,
                'driving_license'  => $user->driving_license ?? null,
            ];

            $image_paths = [
                'base_url'              => url("/"),
                'national_id_path'      => "public/frontend/user-national-id",
                'driving_license_path'  => "public/frontend/user-driving-license",
            ];

            $instructions = [
                'national_id'       => "Image URL path for national ID document",
                'driving_license'   => "Image URL path for driving license document",
            ];

            return Response::success(
                [__('Identification info fetched successfully!')],
                [
                    'instructions'      => $instructions,
                    'identification'    => $response_data,
                    'image_paths'       => $image_paths,
                ],
                200
            );
        } catch (Exception $e) {
            Log::error('Identification Error - getIdentification: ' . $e->getMessage());
            return Response::error(
                [__('Something went wrong! Please try again')],
                [],
                500
            );
        }
    }

    /**
     * Upload user's national ID image
     *
     * POST /api/v1/user/identification/national-id/upload
     *
     * Form Data (multipart/form-data):
     * {
     *   "national_id": <image_file>
     * }
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function uploadNationalId(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'national_id' => "required|image|mimes:jpg,png,jpeg,gif,webp|max:10240",
        ]);

        if ($validator->fails()) {
            return Response::error($validator->errors()->all(), [], 422);
        }

        try {
            $user = auth()->guard("api")->user();

            // Delete old national ID image if exists
            if ($user->national_id) {
                delete_file($user->national_id);
            }

            // Upload new national ID image
            $image = upload_file($request->file('national_id'), 'junk-files', null);
            $upload_image = upload_files_from_path_dynamic([$image['dev_path']], 'user-national-id');
            delete_file($image['dev_path']);

            $user->update([
                'national_id' => $upload_image,
            ]);

            return Response::success(
                [__('National ID image successfully uploaded!')],
                [
                    'national_id' => $user->national_id,
                ],
                200
            );
        } catch (Exception $e) {
            return Response::error(
                [__('Something went wrong! Please try again')],
                [],
                500
            );
        }
    }

    /**
     * Delete user's national ID image
     *
     * DELETE /api/v1/user/identification/national-id/delete
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteNationalId()
    {
        try {
            $user = auth()->guard("api")->user();

            if (!$user->national_id) {
                return Response::error(
                    [__('No national ID image found to delete')],
                    [],
                    400
                );
            }

            // Delete the file
            delete_file($user->national_id);

            $user->update([
                'national_id' => null,
            ]);

            return Response::success(
                [__('National ID image successfully deleted!')],
                [],
                200
            );
        } catch (Exception $e) {
            return Response::error(
                [__('Something went wrong! Please try again')],
                [],
                500
            );
        }
    }

    /**
     * Upload user's driving license image
     *
     * POST /api/v1/user/identification/driving-license/upload
     *
     * Form Data (multipart/form-data):
     * {
     *   "driving_license": <image_file>
     * }
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function uploadDrivingLicense(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'driving_license' => "required|image|mimes:jpg,png,jpeg,gif,webp|max:10240",
        ]);

        if ($validator->fails()) {
            return Response::error($validator->errors()->all(), [], 422);
        }

        try {
            $user = auth()->guard("api")->user();

            // Delete old driving license image if exists
            if ($user->driving_license) {
                delete_file($user->driving_license);
            }

            // Upload new driving license image
            $image = upload_file($request->file('driving_license'), 'junk-files', null);
            $upload_image = upload_files_from_path_dynamic([$image['dev_path']], 'user-driving-license');
            delete_file($image['dev_path']);

            $user->update([
                'driving_license' => $upload_image,
            ]);

            return Response::success(
                [__('Driving license image successfully uploaded!')],
                [
                    'driving_license' => $user->driving_license,
                ],
                200
            );
        } catch (Exception $e) {
            return Response::error(
                [__('Something went wrong! Please try again')],
                [],
                500
            );
        }
    }

    /**
     * Delete user's driving license image
     *
     * DELETE /api/v1/user/identification/driving-license/delete
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteDrivingLicense()
    {
        try {
            $user = auth()->guard("api")->user();

            if (!$user->driving_license) {
                return Response::error(
                    [__('No driving license image found to delete')],
                    [],
                    400
                );
            }

            // Delete the file
            delete_file($user->driving_license);

            $user->update([
                'driving_license' => null,
            ]);

            return Response::success(
                [__('Driving license image successfully deleted!')],
                [],
                200
            );
        } catch (Exception $e) {
            return Response::error(
                [__('Something went wrong! Please try again')],
                [],
                500
            );
        }
    }

    /**
     * Get both national ID and driving license images together
     *
     * GET /api/v1/user/identification/all
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAllIdentifications()
    {
        try {
            $user = auth()->guard("api")->user();

            $image_paths = [
                'base_url'              => url("/"),
                'national_id_path'      => "public/frontend/user-national-id",
                'driving_license_path'  => "public/frontend/user-driving-license",
            ];

            return Response::success(
                [__('All identification images fetched successfully!')],
                [
                    'user_id'                        => $user->id,
                    'national_id'                    => $user->national_id ?? null,
                    'driving_license'                => $user->driving_license ?? null,
                    'identification_complete'        => !empty($user->national_id) && !empty($user->driving_license),
                    'image_paths'                    => $image_paths,
                ],
                200
            );
        } catch (Exception $e) {
            Log::error('Identification Error - getAllIdentifications: ' . $e->getMessage());
            return Response::error(
                [__('Something went wrong! Please try again')],
                [],
                500
            );
        }
    }
}
