@if($referrals->hasPages())
    <div class="d-flex justify-content-center py-3">
        {{ $referrals->links() }}
    </div>
@endif
