jQuery(document).ready(function ($) {
    var progressBar = $('#progress-bar');
    var progressBarContainer = $('#progress-bar-container');
    var conversionStatus = $('#conversion-status');

    $('#convert-form').on('submit', function (e) {
        var clickedButton = $('input[type=submit]:focus', this);

        if (clickedButton.val() === 'Convert') {
            e.preventDefault();

            $('input[type=submit]', this).prop('disabled', true);

            progressBarContainer.show();
            progressBar.css('width', '0%');
            conversionStatus.html('');

            convertImages(0);
        }
    });

    function convertImages(current) {
        var formData = $('#convert-form').serialize() + '&action=convert_images&security=' + webp_avif_converter.nonce + '&current=' + current;

        $.ajax({
            url: webp_avif_converter.ajax_url,
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function (response) {
                console.log('AJAX response:', response);
                if (response.success) {
                    updateProgressBar(response.data.progress);
                    conversionStatus.html('Converting image ' + response.data.current + ' of ' + response.data.total);
                    if (response.data.progress < 100) {
                        convertImages(response.data.current);
                    } else {
                        conversionStatus.html(response.data.message);
                        $('input[type=submit]', '#convert-form').prop('disabled', false);
                    }
                } else {
                    conversionStatus.html('Error: ' + (response.data ? response.data.message : 'Unknown error'));
                    $('input[type=submit]', '#convert-form').prop('disabled', false);
                }
            },
            error: function (jqXHR, textStatus, errorThrown) {
                console.error('AJAX error:', textStatus, errorThrown);
                conversionStatus.html('An error occurred. Please check the browser console for more details.');
                $('input[type=submit]', '#convert-form').prop('disabled', false);
            }
        });
    }

    function updateProgressBar(progress) {
        const circle = $('#progress-bar-container .circle');
        const percentageText = $('#progress-bar-container .percentage');
        const radius = 15.9155; // Radius of the circle from the SVG
        const circumference = 2 * Math.PI * radius;

        const strokeDasharray = circumference;
        const strokeDashoffset = ((100 - progress) / 100) * circumference;

        circle.css('stroke-dasharray', strokeDasharray + ' ' + strokeDasharray);
        circle.css('stroke-dashoffset', strokeDashoffset);
        percentageText.text(Math.round(progress) + '%');
    }
});

const switcher = document.querySelector('#theme-switcher')
const doc = document.firstElementChild

switcher.addEventListener('input', e =>
    setTheme(e.target.value))

const setTheme = theme =>
    doc.setAttribute('color-scheme', theme)

document.addEventListener('DOMContentLoaded', function() {
    const themeSwitcher = document.getElementById('theme-switcher');
    const setThemeButton = document.getElementById('set-theme-button');
    
    // Function to set a cookie
    function setCookie(name, value, days) {
        let expires = "";
        if (days) {
            const date = new Date();
            date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
            expires = "; expires=" + date.toUTCString();
        }
        document.cookie = name + "=" + (value || "")  + expires + "; path=/";
    }

    // Function to get a cookie
    function getCookie(name) {
        const nameEQ = name + "=";
        const ca = document.cookie.split(';');
        for(let i=0; i < ca.length; i++) {
            let c = ca[i];
            while (c.charAt(0) == ' ') c = c.substring(1, c.length);
            if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length, c.length);
        }
        return null;
    }

    // Set the initial theme based on the cookie
    const savedTheme = getCookie('webp_avif_color_scheme');
    if (savedTheme) {
        document.querySelector(`input[name="color-scheme"][value="${savedTheme}"]`).checked = true;
        document.documentElement.classList.add(savedTheme);
        document.documentElement.setAttribute('color-scheme', savedTheme);
    }

    // Function to apply the selected theme
    function applyTheme() {
        const selectedTheme = document.querySelector('input[name="color-scheme"]:checked').value;
        document.documentElement.className = '';  // Remove all classes
        document.documentElement.classList.add(selectedTheme);
        document.documentElement.setAttribute('color-scheme', selectedTheme);
        setCookie('webp_avif_color_scheme', selectedTheme, 365); // Save for 1 year
        alert('Motyw został ustawiony: ' + selectedTheme);
    }

    // Listen for clicks on the set theme button
    setThemeButton.addEventListener('click', applyTheme);

    // Optional: Apply theme immediately when radio button is changed
    themeSwitcher.addEventListener('change', function(e) {
        if (e.target && e.target.name === 'color-scheme') {
            const selectedTheme = e.target.value;
            document.documentElement.className = '';  // Remove all classes
            document.documentElement.classList.add(selectedTheme);
            document.documentElement.setAttribute('color-scheme', selectedTheme);
            // Note: We don't set the cookie here, only when the button is clicked
        }
    });
});