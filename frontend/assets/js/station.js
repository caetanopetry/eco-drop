/**
 * EcoDrop — estação demonstrativa.
 * Copia o link de associação da sessão para a área de transferência, com
 * fallback para navegadores/contextos sem Clipboard API.
 */
(function () {
    'use strict';

    function copiarTexto(texto) {
        if (navigator.clipboard && window.isSecureContext) {
            return navigator.clipboard.writeText(texto);
        }

        return new Promise(function (resolver, rejeitar) {
            var area = document.createElement('textarea');
            area.value = texto;
            area.setAttribute('readonly', '');
            area.style.position = 'fixed';
            area.style.opacity = '0';
            document.body.appendChild(area);
            area.select();

            var copiado = false;
            try {
                copiado = document.execCommand('copy');
            } catch (erro) {
                copiado = false;
            }

            document.body.removeChild(area);
            copiado ? resolver() : rejeitar(new Error('copy-falhou'));
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        var botoes = document.querySelectorAll('[data-copiar]');

        botoes.forEach(function (botao) {
            botao.addEventListener('click', function () {
                var seletor = botao.getAttribute('data-copiar');
                var alvo = seletor ? document.querySelector(seletor) : null;
                if (!alvo) {
                    return;
                }

                var texto = alvo.value || alvo.textContent || '';
                var rotulo = botao.textContent;
                var restaurar = function (mensagem) {
                    botao.textContent = mensagem;
                    window.setTimeout(function () {
                        botao.textContent = rotulo;
                    }, 2200);
                };

                copiarTexto(texto).then(function () {
                    restaurar('Link copiado!');
                }).catch(function () {
                    if (typeof alvo.select === 'function') {
                        alvo.select();
                        alvo.setSelectionRange(0, texto.length);
                    }
                    restaurar('Selecione e copie');
                });
            });
        });
    });
})();
