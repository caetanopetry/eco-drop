document.querySelectorAll('.conta-avatar img, .perfil-avatar img').forEach((foto) => {
    const ocultarFoto = () => { foto.hidden = true; };
    foto.addEventListener('error', ocultarFoto);
    if (foto.complete && foto.naturalWidth === 0) ocultarFoto();
});
