<x-site.topbar
    :left-text="$topbarLeft"
    :center-text="$topbarCenter"
    :right-text="$topbarRight"
/>

<x-site.header
    :nav-links="$navLinks"
    :cart-count="$cartCount"
    :notification-count="$notificationCount ?? 0"
/>
