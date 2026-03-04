// ========== РЕДАКТИРОВАНИЕ СОТРУДНИКА (МОДАЛЬНОЕ ОКНО) ==========
document.addEventListener('DOMContentLoaded', function() {
    let editModal = null;

    // Инициализация модального окна Bootstrap
    const modalElement = document.getElementById('editEmployeeModal');
    if (modalElement) {
        editModal = new bootstrap.Modal(modalElement, {
            backdrop: 'static',
            keyboard: false
        });
    }

    // Функция для получения данных сотрудника из строки
    function getEmployeeDataFromRow(row) {
        try {
            const employeeCell = row.querySelector('.employee-info');
            const nameElement = employeeCell.querySelector('.employee-name');
            const detailsElement = employeeCell.querySelector('.employee-details');
            const idElement = detailsElement.querySelector('.employee-id');
            const deptElement = detailsElement.querySelector('.employee-department');

            // Парсим ФИО
            const nameText = nameElement.textContent.trim();
            const nameParts = nameText.split(' ');

            return {
                id: row.dataset.employeeId,
                lastName: nameParts[0] || '',
                firstName: nameParts[1] || '',
                middleName: nameParts[2] || '',
                tabNumber: idElement.textContent.replace('№', ''),
                departmentName: deptElement.textContent.trim()
            };
        } catch (error) {
            console.error('Error parsing employee data:', error);
            return null;
        }
    }

    // Функция для поиска ID отдела по названию
    function findDepartmentIdByName(departmentName) {
        const select = document.getElementById('edit_department_id');
        if (!select) return '';

        for (let option of select.options) {
            if (option.text.trim() === departmentName.trim()) {
                return option.value;
            }
        }
        return '';
    }

    // Функция для заполнения формы данными сотрудника
    function fillEditForm(employeeData) {
        try {
            document.getElementById('edit_last_name').value = employeeData.lastName || '';
            document.getElementById('edit_first_name').value = employeeData.firstName || '';
            document.getElementById('edit_middle_name').value = employeeData.middleName || '';
            document.getElementById('edit_tab_number').value = employeeData.tabNumber || '';
            document.getElementById('edit_employee_id_display').textContent = employeeData.id || '';

            // Устанавливаем отдел
            const departmentId = findDepartmentIdByName(employeeData.departmentName);
            const departmentSelect = document.getElementById('edit_department_id');
            if (departmentSelect) {
                departmentSelect.value = departmentId;
            }

            // Обновляем action формы
            const form = document.getElementById('editEmployeeForm');
            if (form) {
                // Используем правильный URL с ID сотрудника
                form.action = `/employees/${employeeData.id}`;
            }
        } catch (error) {
            console.error('Error filling form:', error);
        }
    }

    // Обработчик кнопки редактирования
    const editButtons = document.querySelectorAll('.edit-btn');
    console.log('Found edit buttons:', editButtons.length); // Для отладки

    editButtons.forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();

            const row = this.closest('tr');
            if (!row) {
                console.error('Row not found');
                return;
            }

            const employeeData = getEmployeeDataFromRow(row);
            if (!employeeData) {
                showNotification('error', 'Не удалось получить данные сотрудника');
                return;
            }

            console.log('Editing employee:', employeeData); // Для отладки

            // Заполняем форму данными
            fillEditForm(employeeData);

            // Открываем модальное окно
            if (editModal) {
                editModal.show();
            }
        });
    });

    // Обработчик отправки формы
    const editForm = document.getElementById('editEmployeeForm');
    if (editForm) {
        editForm.addEventListener('submit', function(e) {
            e.preventDefault();

            // Проверка валидации
            if (!this.checkValidity()) {
                e.stopPropagation();
                this.classList.add('was-validated');

                // Подсвечиваем первое поле с ошибкой
                const firstInvalid = this.querySelector(':invalid');
                if (firstInvalid) {
                    firstInvalid.focus();
                }
                return;
            }

            // Находим кнопку отправки (ищем внутри формы или в модальном окне)
            const submitBtn = document.querySelector('#editEmployeeModal button[type="submit"]');

            // Сохраняем оригинальный текст кнопки
            let originalText = 'Сохранить';
            if (submitBtn) {
                originalText = submitBtn.innerHTML;
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Сохранение...';
                submitBtn.disabled = true;
            }

            // Получаем ID сотрудника из URL
            const urlParts = this.action.split('/');
            const employeeId = urlParts[urlParts.length - 1];

            if (!employeeId) {
                showNotification('error', 'ID сотрудника не найден');
                if (submitBtn) {
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                }
                return;
            }

            // Собираем данные формы
            const formData = new FormData(this);

            // Добавляем метод PUT для Laravel
            formData.append('_method', 'PUT');

            // Отправляем AJAX запрос
            fetch(this.action, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            })
                .then(response => {
                    if (!response.ok) {
                        return response.json().then(err => Promise.reject(err));
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        // Закрываем модальное окно
                        editModal.hide();

                        // Обновляем данные в таблице
                        updateEmployeeInTable(data.employee);

                        // Показываем уведомление об успехе
                        showNotification('success', 'Данные сотрудника успешно обновлены');
                    } else {
                        // Показываем ошибки
                        if (data.errors) {
                            showValidationErrors(data.errors);
                        } else {
                            showNotification('error', data.message || 'Ошибка при сохранении данных');
                        }
                    }
                })
                .catch(error => {
                    console.error('Error details:', error);

                    let errorMessage = 'Произошла ошибка при отправке запроса';

                    if (error.message) {
                        errorMessage = error.message;
                    } else if (error.errors) {
                        showValidationErrors(error.errors);
                        return;
                    } else if (error.status === 404) {
                        errorMessage = 'URL не найден. Проверьте маршрут.';
                    } else if (error.status === 419) {
                        errorMessage = 'Сессия истекла. Обновите страницу.';
                    } else if (error.status === 500) {
                        errorMessage = 'Внутренняя ошибка сервера';
                    }

                    showNotification('error', errorMessage);
                })
                .finally(() => {
                    // Возвращаем кнопку в исходное состояние
                    if (submitBtn) {
                        submitBtn.innerHTML = originalText;
                        submitBtn.disabled = false;
                    }
                });
        });
    } else {
        console.error('Edit form not found!');
    }

    // Функция обновления данных сотрудника в таблице
    function updateEmployeeInTable(employee) {
        try {
            const row = document.querySelector(`tr[data-employee-id="${employee.id}"]`);
            if (!row) {
                console.error('Row not found for employee:', employee.id);
                return;
            }

            const employeeCell = row.querySelector('.employee-info');
            if (!employeeCell) {
                console.error('Employee cell not found');
                return;
            }

            const fullName = [employee.last_name, employee.first_name, employee.middle_name]
                .filter(name => name && name.trim() !== '')
                .join(' ');

            employeeCell.innerHTML = `
                <span class="employee-name">${fullName}</span>
                <div class="employee-details">
                    <span class="employee-id">№${employee.tab_number}</span>
                    <span class="employee-department">${employee.department_name}</span>
                </div>
            `;
        } catch (error) {
            console.error('Error updating table:', error);
        }
    }

    // Функция показа уведомлений
    function showNotification(type, message) {
        // Удаляем предыдущие уведомления
        document.querySelectorAll('.custom-notification').forEach(el => el.remove());

        // Создаем элемент уведомления
        const notification = document.createElement('div');
        notification.className = `custom-notification alert alert-${type === 'success' ? 'success' : 'danger'} alert-dismissible fade show position-fixed top-0 end-0 m-3`;
        notification.style.zIndex = '9999';
        notification.style.minWidth = '300px';
        notification.style.boxShadow = '0 4px 12px rgba(0,0,0,0.15)';
        notification.style.border = 'none';

        const icon = type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle';

        notification.innerHTML = `
            <div class="d-flex align-items-center">
                <i class="fas ${icon} me-2" style="font-size: 1.2rem;"></i>
                <span>${message}</span>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        `;

        document.body.appendChild(notification);

        // Автоматически скрываем через 3 секунды
        setTimeout(() => {
            notification.classList.remove('show');
            setTimeout(() => notification.remove(), 300);
        }, 3000);
    }

    // Функция показа ошибок валидации
    function showValidationErrors(errors) {
        // Сначала убираем предыдущие ошибки
        document.querySelectorAll('.is-invalid').forEach(el => {
            el.classList.remove('is-invalid');
        });

        for (const [field, messages] of Object.entries(errors)) {
            const input = document.getElementById(`edit_${field}`);
            if (input) {
                input.classList.add('is-invalid');

                // Ищем или создаем feedback элемент
                let feedback = input.nextElementSibling;
                if (!feedback || !feedback.classList.contains('invalid-feedback')) {
                    feedback = document.createElement('div');
                    feedback.className = 'invalid-feedback';
                    input.parentNode.insertBefore(feedback, input.nextSibling);
                }
                feedback.textContent = Array.isArray(messages) ? messages[0] : messages;
            }
        }
    }

    // Сброс валидации при закрытии модального окна
    if (modalElement) {
        modalElement.addEventListener('hidden.bs.modal', function () {
            const form = document.getElementById('editEmployeeForm');
            if (form) {
                form.classList.remove('was-validated');
                form.querySelectorAll('.is-invalid').forEach(el => {
                    el.classList.remove('is-invalid');
                });
            }
        });
    }
});
