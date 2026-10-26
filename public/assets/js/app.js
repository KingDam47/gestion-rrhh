document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('[data-toggle-password]').forEach((button) => {
    button.addEventListener('click', () => {
      const selector = button.getAttribute('data-toggle-password');
      const input = selector ? document.querySelector(selector) : null;

      if (!input) {
        return;
      }

      const isPassword = input.getAttribute('type') === 'password';
      input.setAttribute('type', isPassword ? 'text' : 'password');
      button.setAttribute('aria-label', isPassword ? 'Ocultar contraseña' : 'Mostrar contraseña');

      const icon = button.querySelector('i');
      if (icon) {
        icon.classList.toggle('bi-eye', !isPassword);
        icon.classList.toggle('bi-eye-slash', isPassword);
      }
    });
  });

  const departmentSelect = document.querySelector('[data-employee-department]');
  const positionSelect = document.querySelector('[data-employee-position]');

  if (departmentSelect && positionSelect) {
    const filterPositions = () => {
      const departmentId = departmentSelect.value;
      let selectedIsVisible = false;

      Array.from(positionSelect.options).forEach((option) => {
        if (option.value === '') {
          option.hidden = false;
          option.disabled = false;
          return;
        }

        const matches = option.dataset.department === departmentId;
        option.hidden = !matches;
        option.disabled = !matches;

        if (matches && option.selected) {
          selectedIsVisible = true;
        }
      });

      if (!selectedIsVisible) {
        positionSelect.value = '';
      }
    };

    departmentSelect.addEventListener('change', filterPositions);
    filterPositions();
  }

  const attendanceStatus = document.querySelector('[data-attendance-status]');
  const attendanceTimes = document.querySelector('[data-attendance-times]');

  if (attendanceStatus && attendanceTimes) {
    const updateAttendanceTimes = () => {
      const isAbsent = attendanceStatus.value === 'absent';

      attendanceTimes.querySelectorAll('input').forEach((input) => {
        input.disabled = isAbsent;

        if (isAbsent) {
          input.value = '';
        }
      });

      attendanceTimes.classList.toggle('opacity-50', isAbsent);
    };

    attendanceStatus.addEventListener('change', updateAttendanceTimes);
    updateAttendanceTimes();
  }

  document.querySelectorAll('form[data-confirm]').forEach((form) => {
    form.addEventListener('submit', (event) => {
      const message = form.getAttribute('data-confirm');

      if (message && !window.confirm(message)) {
        event.preventDefault();
      }
    });
  });

  document.querySelectorAll('[data-print]').forEach((button) => {
    button.addEventListener('click', () => window.print());
  });
});
