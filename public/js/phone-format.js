document.addEventListener('DOMContentLoaded', function () {
    const phoneInput = document.querySelector('.phone-input');

    // Bloquer les caractères non numériques
    phoneInput.addEventListener('keypress', function (e) {
        // Vérifier si la touche pressée est un chiffre
        if (!/[0-9]/.test(e.key)) {
            e.preventDefault(); // Empêche la saisie si ce n'est pas un chiffre
        }
    });

    // Formater le numéro avec des points
    phoneInput.addEventListener('input', function (e) {
        let input = e.target.value.replace(/\D/g, '');
        let formattedInput = '';

        for (let i = 0; i < input.length; i++) {
            if (i % 2 === 0 && i !== 0) {
                formattedInput += '.' + input[i];
            } else {
                formattedInput += input[i];
            }
        }

        e.target.value = formattedInput;

        // Quitter le champ si le numéro est complet (14 caractères pour 10 chiffres + 4 points)
        if (formattedInput.length >= 14) {
            e.target.blur(); // Quitte le champ de saisie
        }
    });
});