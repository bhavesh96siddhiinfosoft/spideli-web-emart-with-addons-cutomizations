{{-- Point 41: the choice "pills" used by the parcel form and the carrier
     list, in the site's own orange (#D17E19). Scoped to .p41-* so nothing
     else on the site is affected. --}}
<style>
    .p41-subtitle { font-weight: 600; font-size: 15px; margin-bottom: 8px; color: #333; }
    .p41-choice-group { display: flex; flex-wrap: wrap; gap: 10px; }
    .p41-choice-stack { flex-direction: column; }
    .p41-choice { margin: 0; cursor: pointer; }
    .p41-choice input { position: absolute; opacity: 0; pointer-events: none; }
    .p41-choice span {
        display: inline-flex; align-items: center; padding: 10px 18px;
        border: 1px solid #E2E2E2; border-radius: 24px; background: #fff;
        font-size: 15px; color: #333; transition: all .15s ease;
    }
    .p41-choice-stack .p41-choice span { display: flex; border-radius: 12px; width: 100%; }
    .p41-choice input:checked + span { border-color: #D17E19; background: #FFF5EA; color: #D17E19; font-weight: 600; }
    .p41-choice input:focus-visible + span { outline: 2px solid #D17E19; outline-offset: 2px; }
    .p41-point-select { border-radius: 10px; }

    /* Checkout: the carrier list */
    .p41-carriers .p41-carrier {
        display: flex; align-items: center; gap: 12px; padding: 12px 14px; margin-bottom: 10px;
        border: 1px solid #E2E2E2; border-radius: 12px; cursor: pointer; background: #fff;
    }
    .p41-carriers .p41-carrier.is-selected { border-color: #D17E19; background: #FFF5EA; }
    .p41-carrier img, .p41-carrier .p41-carrier-icon {
        width: 44px; height: 44px; border-radius: 10px; object-fit: contain; flex: 0 0 44px;
        background: #FFF0E0; display: flex; align-items: center; justify-content: center; color: #D17E19;
    }
    .p41-carrier .p41-carrier-name { font-weight: 600; color: #222; }
    .p41-carrier .p41-carrier-sub { font-size: 13px; color: #777; }
    .p41-carrier .p41-carrier-price { margin-left: auto; font-weight: 700; color: #222; white-space: nowrap; }
    .p41-chip { display: inline-block; padding: 3px 10px; border-radius: 12px; background: #D17E19; color: #fff; font-size: 13px; }
    .p41-breakdown .d-flex { padding: 4px 0; }
</style>
