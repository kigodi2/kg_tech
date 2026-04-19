<?php
use App\Models\User;

$user = User::where('email', 'agreykigodi@gmail.com')->first();
if ($user) {
    $user->portal_role = 'admin';
    $user->save();
    echo "User agreykigodi@gmail.com is now an Admin.\n";
} else {
    echo "User agreykigodi@gmail.com not found.\n";
}
