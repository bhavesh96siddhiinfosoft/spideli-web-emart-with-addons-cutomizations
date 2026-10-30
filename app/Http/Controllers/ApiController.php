<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Exception;
use App\Models\VendorUsers;
use App\Models\User;
use Illuminate\Http\Request;

class ApiController extends Controller
{
    public function deleteUserFromDb(Request $request) {

        /* Point 84: an app that sends the user's Firebase ID token gets the whole
         * delete - the sign-in removed (so the email can sign up again), the
         * customer profile removed (hard delete, as the admin), and the rows below.
         * Without the header the old behaviour stays, until the app sends it. */
        if ($request->bearerToken()) {
            return $this->deleteSignedInUser($request);
        }

        $validator = Validator::make($request->all(), [
            'uuid' => 'required|exists:vendor_users,uuid',  
        ]);
    
        if ($validator->fails()) {
            return response()->json([
                'status' => 'okay',
                'message' => $validator->errors()->first(), 
            ], 400);
        }
    
        DB::beginTransaction();
    
        try {
            $vendorUser = VendorUsers::where('uuid', $request->uuid)->first();
            if ($vendorUser) {
                $user_id = $vendorUser->user_id;  
                $user = User::find($user_id);
                if ($user) {
                    $user->delete();  
                } else {
                    return response()->json([
                        'status' => 'okay',
                        'message' => 'User not found with the provided user_id.',
                    ], 404);
                }
                $vendorUser->delete();
            } else {
                return response()->json([
                    'status' => 'okay',
                    'message' => 'No associated vendor user found with the provided UUID.',
                ], 404);
            }

            DB::commit();
    
            return response()->json([
                'status' => 'okay',
                'message' => 'User and associated records deleted successfully.',
            ], 200);
    
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete user. ' . $e->getMessage(),
            ], 500);
        }
    }

    /* The signed-in user deletes their own account. The token says who they
     * are; a uuid in the body, when sent, must be the same user. */
    private function deleteSignedInUser(Request $request)
    {
        $accounts = new \App\Services\FirebaseAccounts();
        try {
            $uid = $accounts->verifyIdToken($request->bearerToken());
        } catch (Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'The sign-in could not be checked. Try again.'], 503);
        }
        if (!$uid) {
            return response()->json(['status' => 'error', 'message' => 'Sign in again, then delete the account.'], 401);
        }
        if ($request->filled('uuid') && (string)$request->input('uuid') !== $uid) {
            return response()->json(['status' => 'error', 'message' => 'You can only delete your own account.'], 403);
        }

        try {
            $accounts->deleteProfile($uid);
            $accounts->deleteSignIn($uid);
        } catch (Exception $e) {
            report($e);
            return response()->json(['status' => 'error', 'message' => 'The account could not be deleted. Try again.'], 500);
        }

        /* The website's own rows, when this user ever signed in here. */
        DB::transaction(function () use ($uid) {
            $vendorUser = VendorUsers::where('uuid', $uid)->first();
            if ($vendorUser) {
                User::where('id', $vendorUser->user_id)->delete();
                $vendorUser->delete();
            }
        });

        return response()->json(['status' => 'okay', 'message' => 'Account deleted.'], 200);
    }
}
