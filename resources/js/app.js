

import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();

const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
let pointerX = 0;
let pointerY = 0;
let frame = null;

function onMouseMove(event) {
    pointerX = event.clientX;
    pointerY = event.clientY;

    if (frame !== null) {
        return;
    }

    frame = window.requestAnimationFrame(() => {
        document.body.style.setProperty('--mouse-x', `${pointerX}px`);
        document.body.style.setProperty('--mouse-y', `${pointerY}px`);
        frame = null;
    });
}

function syncGlow() {
    if (reduceMotion.matches) {
        document.removeEventListener('mousemove', onMouseMove);
        document.body.style.removeProperty('--mouse-x');
        document.body.style.removeProperty('--mouse-y');
    } else {
        document.addEventListener('mousemove', onMouseMove, { passive: true });
    }
}

syncGlow();
reduceMotion.addEventListener('change', syncGlow);
