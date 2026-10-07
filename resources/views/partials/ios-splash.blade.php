{{-- iOS splash: iPhone portrait only (basic). Android splash comes from manifest.json. --}}
@php($iconV = $iconV ?? filemtime(public_path('images/favicon-512.png')))
{{-- iPhone SE 2-3 / 8 --}}
<link rel="apple-touch-startup-image" media="screen and (device-width: 375px) and (device-height: 667px) and (-webkit-device-pixel-ratio: 2) and (orientation: portrait)" href="{{ asset('images/splash/apple-splash-750x1334.png') }}?v={{ $iconV }}">
{{-- iPhone XR / 11 --}}
<link rel="apple-touch-startup-image" media="screen and (device-width: 414px) and (device-height: 896px) and (-webkit-device-pixel-ratio: 2) and (orientation: portrait)" href="{{ asset('images/splash/apple-splash-828x1792.png') }}?v={{ $iconV }}">
{{-- iPhone 12-13-14 --}}
<link rel="apple-touch-startup-image" media="screen and (device-width: 390px) and (device-height: 844px) and (-webkit-device-pixel-ratio: 3) and (orientation: portrait)" href="{{ asset('images/splash/apple-splash-1170x2532.png') }}?v={{ $iconV }}">
{{-- iPhone 14 Pro / 15 / 16 --}}
<link rel="apple-touch-startup-image" media="screen and (device-width: 393px) and (device-height: 852px) and (-webkit-device-pixel-ratio: 3) and (orientation: portrait)" href="{{ asset('images/splash/apple-splash-1179x2556.png') }}?v={{ $iconV }}">
{{-- iPhone 16 Pro --}}
<link rel="apple-touch-startup-image" media="screen and (device-width: 402px) and (device-height: 874px) and (-webkit-device-pixel-ratio: 3) and (orientation: portrait)" href="{{ asset('images/splash/apple-splash-1206x2622.png') }}?v={{ $iconV }}">
{{-- iPhone 14 Plus / 13 Pro Max --}}
<link rel="apple-touch-startup-image" media="screen and (device-width: 428px) and (device-height: 926px) and (-webkit-device-pixel-ratio: 3) and (orientation: portrait)" href="{{ asset('images/splash/apple-splash-1284x2778.png') }}?v={{ $iconV }}">
{{-- iPhone 15 Plus / 16 Plus / 15 Pro Max --}}
<link rel="apple-touch-startup-image" media="screen and (device-width: 430px) and (device-height: 932px) and (-webkit-device-pixel-ratio: 3) and (orientation: portrait)" href="{{ asset('images/splash/apple-splash-1290x2796.png') }}?v={{ $iconV }}">
{{-- iPhone 16 Pro Max --}}
<link rel="apple-touch-startup-image" media="screen and (device-width: 440px) and (device-height: 956px) and (-webkit-device-pixel-ratio: 3) and (orientation: portrait)" href="{{ asset('images/splash/apple-splash-1320x2868.png') }}?v={{ $iconV }}">
