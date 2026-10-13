document.addEventListener('DOMContentLoaded', function () {
    var PX_PER_SECOND = 60; // vitesse cible - ajuste ce chiffre pour accélérer/ralentir

    var track = document.querySelector('.ld-brands--loop .ld-brands__track');
    if (!track) {
        return;
    }

    var row = track.querySelector('.ld-brands__row:not(.ld-brands__row--dup)');
    if (!row) {
        return;
    }

    function applyDuration() {
        var width = row.offsetWidth;
        if (!width) {
            return;
        }
        var duration = width / PX_PER_SECOND;
        track.style.animationDuration = duration + 's';
    }

    // La largeur réelle ne se connaît qu'une fois les logos chargés (leur
    // taille dépend de leurs propres dimensions naturelles) - recalcule au
    // chargement de chaque image, et une dernière fois quand tout est chargé.
    var images = row.querySelectorAll('img');
    var remaining = images.length;

    if (remaining === 0) {
        applyDuration();
        return;
    }

    images.forEach(function (img) {
        if (img.complete) {
            remaining--;
        } else {
            img.addEventListener('load', function () {
                remaining--;
                if (remaining <= 0) {
                    applyDuration();
                }
            });
        }
    });

    if (remaining <= 0) {
        applyDuration();
    }

    // Recalcule aussi si la fenêtre change de taille (rotation d'écran,
    // redimensionnement du navigateur) - la largeur de la bande peut changer.
    var resizeTimeout;
    window.addEventListener('resize', function () {
        clearTimeout(resizeTimeout);
        resizeTimeout = setTimeout(applyDuration, 200);
    });
});
