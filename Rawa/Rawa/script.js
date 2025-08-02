document.addEventListener('DOMContentLoaded', function() {
    // Dropdown functionality
    const dropdownButton = document.querySelector('.dropbtn');
    const dropdownContent = document.getElementById('loginDropdown');
    const dropdownArrow = document.querySelector('.dropdown-arrow');

    function toggleDropdown() {
        dropdownContent.classList.toggle('show');
        if (dropdownContent.classList.contains('show')) {
            dropdownArrow.style.transform = 'rotate(180deg)';
        } else {
            dropdownArrow.style.transform = 'rotate(0deg)';
        }
    }

    dropdownButton.addEventListener('click', function(event) {
        event.stopPropagation();
        toggleDropdown();
    });

    window.addEventListener('click', function(event) {
        if (!event.target.matches('.dropbtn') && !event.target.closest('.dropdown-content')) {
            if (dropdownContent.classList.contains('show')) {
                dropdownContent.classList.remove('show');
                dropdownArrow.style.transform = 'rotate(0deg)';
            }
        }
    });

    // Service cards animation
    const serviceCards = document.querySelectorAll('.service-card');

    const observerOptions = {
        root: null, // viewport
        rootMargin: '0px',
        threshold: 0.1 // عندما يكون 10% من العنصر مرئياً
    };

    const serviceObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach((entry, index) => {
            if (entry.isIntersecting) {
                const card = entry.target;
                // Apply animation with a slight delay for staggered effect
                setTimeout(() => {
                    card.classList.add('visible'); // إضافة كلاس 'visible' لتفعيل الرسوم المتحركة
                }, index * 150); // تأخير كل بطاقة بـ 150 مللي ثانية إضافية
                observer.unobserve(card); // توقف عن المراقبة بعد ظهور البطاقة
            }
        });
    }, observerOptions);

    serviceCards.forEach(card => {
        serviceObserver.observe(card);
    });
});