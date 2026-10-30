/*
 * EpickTours public site behaviour (replaces the theme's script.js and
 * SmoothScroll.js, which hijacked the mouse wheel).
 */
(function () {
    'use strict';

    var backTop = document.getElementById('back-top-btn');

    // Show the back-to-top button after scrolling down a little
    function onScroll() {
        if (backTop) {
            backTop.style.display = window.pageYOffset > 400 ? 'block' : 'none';
        }
    }
    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();

    // Smooth scrolling only when following an in-page link (#section), with room for the sticky header
    document.addEventListener('click', function (e) {
        var link = e.target.closest('a[href*="#"]');
        if (!link) return;
        var url = new URL(link.href, window.location.href);
        if (url.pathname !== window.location.pathname || !url.hash || url.hash === '#') return;

        var target;
        try { target = url.hash === '#top' ? document.body : document.querySelector(url.hash); } catch (err) { return; }
        if (!target) return;
        e.preventDefault();

        var header = document.querySelector('.navbar');
        var offset = url.hash === '#top' ? 0 : target.getBoundingClientRect().top + window.pageYOffset - (header ? header.offsetHeight + 10 : 0);
        window.scrollTo({ top: offset, behavior: 'smooth' });
        history.replaceState(null, '', url.hash === '#top' ? url.pathname + url.search : url.hash);

        // Close the mobile menu after choosing a link
        if (window.jQuery) {
            window.jQuery('.navbar-collapse.show').collapse('hide');
        }
    });

    // Arriving from another page with a #section in the URL: correct for the sticky header
    window.addEventListener('load', function () {
        if (!window.location.hash) return;
        var target;
        try { target = document.querySelector(window.location.hash); } catch (err) { return; }
        var header = document.querySelector('.navbar');
        if (target && header) {
            window.scrollTo(0, target.getBoundingClientRect().top + window.pageYOffset - header.offsetHeight - 10);
        }
    });
})();
