/* Luz sobre o lettering em SVG; o rodapé também funciona sem JavaScript. */
(() => {
    'use strict';

    const wordmark = document.querySelector('[data-footer-wordmark]');
    if (!wordmark) return;

    const svg = wordmark.querySelector('svg');
    const footer = wordmark.closest('.eco-footer');
    const gradient = svg.querySelector('radialGradient');
    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
    const finePointer = window.matchMedia('(hover: hover) and (pointer: fine)');
    let frame = 0;
    let pointerX = 0;
    let pointerY = 0;

    const reset = () => {
        window.cancelAnimationFrame(frame);
        frame = 0;
        wordmark.classList.remove('is-hovered');
        gradient.setAttribute('cx', '560');
        gradient.setAttribute('cy', '105');
    };

    const update = () => {
        frame = 0;
        // A matriz do SVG considera o viewBox e o redimensionamento da tela.
        const matrix = svg.getScreenCTM();
        if (!matrix) return;
        const point = svg.createSVGPoint();
        point.x = pointerX;
        point.y = pointerY;
        const position = point.matrixTransform(matrix.inverse());
        gradient.setAttribute('cx', position.x.toFixed(2));
        gradient.setAttribute('cy', position.y.toFixed(2));
    };

    footer.addEventListener('pointermove', (event) => {
        if (reducedMotion.matches || !finePointer.matches || event.pointerType === 'touch') return;
        pointerX = event.clientX;
        pointerY = event.clientY;
        wordmark.classList.add('is-hovered');
        if (!frame) frame = window.requestAnimationFrame(update);
    });

    footer.addEventListener('pointerleave', reset);
    footer.addEventListener('pointercancel', reset);
    reducedMotion.addEventListener('change', reset);
    finePointer.addEventListener('change', reset);
})();
