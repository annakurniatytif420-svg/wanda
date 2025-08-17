<?php
namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Wishlist;

class WishlistController extends Controller
{
    public function index()
    {
        $wishlists = Wishlist::with(['mua.muaProfile', 'mua.services'])
            ->where('customer_id', Auth::id())
            ->get();

        return response()->json($wishlists);
    }

    public function store(Request $request)
    {
        $request->validate([
            'mua_id' => 'required|exists:users,id'
        ]);

        $exists = Wishlist::where('customer_id', Auth::id())
            ->where('mua_id', $request->mua_id)
            ->exists();

        if ($exists) {
            return response()->json(['message' => 'MUA already in wishlist'], 409);
        }

        $wishlist = Wishlist::create([
            'customer_id' => Auth::id(),
            'mua_id'      => $request->mua_id,
        ]);

        return response()->json([
            'message' => 'Added to wishlist',
            'data'    => $wishlist
        ]);
    }

    public function destroy($mua_id)
    {
        Wishlist::where('customer_id', Auth::id())
            ->where('mua_id', $mua_id)
            ->delete();

        return response()->json(['message' => 'Removed from wishlist']);
    }
}
