{{--
    Top-bar drafts icon, next to the approvals bell: forms kept on this device (public/offline/drafts.js)
    waiting to be checked and saved. The list lives on the device, so connection.js fills it in the
    browser and it works with no connection at all. Hidden until there is at least one draft.
--}}
<li class="nav-item dropdown me-2 me-xl-1" id="issobs-drafts-tray" hidden>
  <a class="nav-link dropdown-toggle hide-arrow position-relative px-2" href="javascript:void(0);" data-bs-toggle="dropdown"
     data-bs-auto-close="outside" aria-expanded="false" aria-label="Drafts on this device">
    <i class="icon-base bx bx-edit icon-md"></i>
    <span class="badge rounded-pill bg-warning position-absolute top-0 start-100 translate-middle" data-drafts-count hidden
          style="font-size:.65rem; padding:.25em .45em;"></span>
  </a>
  <div class="dropdown-menu dropdown-menu-end p-0" style="width: 22rem; max-width: calc(100vw - 2rem);">
    <div class="d-flex justify-content-between align-items-center px-3 py-2 border-bottom">
      <h6 class="mb-0">Drafts on this device</h6>
      <span class="badge bg-label-warning rounded-pill" data-drafts-total></span>
    </div>
    <div data-drafts-list></div>
    <div class="px-3 py-2 small text-body-secondary border-top">Open a draft to check it and press Save. Drafts stay on this device only.</div>
  </div>
</li>
