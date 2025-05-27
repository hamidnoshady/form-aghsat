// Ensure this script runs after the DOM is fully loaded
document.addEventListener('DOMContentLoaded', function () {
    // --- Helper to format numbers with Persian thousands separator ---
    function formatNumber(num, usePersianDigits = true) {
        let strNum = String(Math.round(num));
        strNum = strNum.replace(/\B(?=(\d{3})+(?!\d))/g, ",");

        if (usePersianDigits) {
            const persianDigits = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
            strNum = strNum.replace(/\d/g, d => persianDigits[d]);
            strNum = strNum.replace(/,/g, '،');
        }
        return strNum;
    }

    // --- Get DOM Elements (use WP specific IDs) ---
    const totalPriceSlider = document.getElementById('totalPriceSliderWp');
    const totalPriceDisplay = document.getElementById('totalPriceDisplayWp');
    const hiddenTotalPriceInput = document.getElementById('totalPriceWp');

    // const downPaymentSlider = document.getElementById('downPaymentSliderWp'); // Removed
    const downPaymentDisplay = document.getElementById('downPaymentDisplayWp'); // This will now display the calculated 30%
    const hiddenDownPaymentInput = document.getElementById('downPaymentWp');

    const numInstallmentsSelect = document.getElementById('numInstallmentsWp');

    const calculateButton = document.getElementById('calculateButtonWp');
    const resetButton = document.getElementById('resetButtonWp');
    const messageArea = document.getElementById('messageAreaWp');
    const resultsArea = document.getElementById('resultsAreaWp');
    const startDateDisplayInfo = document.getElementById('startDateDisplayInfoWp');
    const installmentsTableBody = document.getElementById('installmentsTableBodyWp');

    const formStep1 = document.getElementById('formStep1Wp');
    const formStep2 = document.getElementById('formStep2Wp');
    const proceedToUploadButton = document.getElementById('proceedToUploadButtonWp');
    const prevButton = document.getElementById('prevButtonWp');
    const submitDocumentsButton = document.getElementById('submitDocumentsButtonWp');

    const chequeImageInput = document.getElementById('chequeImageWp');
    const chequePreview = document.getElementById('chequePreviewWp');
    const nationalIdImageInput = document.getElementById('nationalIdImageWp');
    const nationalIdPreview = document.getElementById('nationalIdPreviewWp');
    const submissionIdInput = document.getElementById('cic_submission_id');


    // --- Event Listeners for Sliders ---
    if (totalPriceSlider && totalPriceDisplay && hiddenTotalPriceInput && downPaymentDisplay && hiddenDownPaymentInput) {
        function updateTotalAndDownPaymentDisplay(totalPriceValue) {
            const numTotalPrice = parseFloat(totalPriceValue);
            if (totalPriceDisplay) totalPriceDisplay.textContent = formatNumber(numTotalPrice);
            if (hiddenTotalPriceInput) hiddenTotalPriceInput.value = numTotalPrice;

            // Calculate 30% down payment
            // Round to nearest 5 million step - this logic might need adjustment if downPaymentSlider.max was used for upper bound.
            // For now, we assume the 30% can be any value based on total price, then rounded.
            const thirtyPercentDown = Math.round((numTotalPrice * 0.3) / 5000000) * 5000000; 
            
            // Since slider is removed, we don't need to check its max.
            // We might want a general max for down payment if it shouldn't exceed total price,
            // but 30% will always be less than or equal to total price.
            const newDownPaymentValue = thirtyPercentDown;

            // if (downPaymentSlider) downPaymentSlider.value = newDownPaymentValue; // Slider removed
            if (downPaymentDisplay) downPaymentDisplay.textContent = formatNumber(newDownPaymentValue);
            if (hiddenDownPaymentInput) hiddenDownPaymentInput.value = newDownPaymentValue;
        }

        totalPriceSlider.addEventListener('input', (event) => {
            updateTotalAndDownPaymentDisplay(event.target.value);
        });
        // Initialize with default total price and set down payment accordingly
        updateTotalAndDownPaymentDisplay(totalPriceSlider.value);

        // Down payment slider event listener is removed as the slider is removed.
        // The down payment is now solely dependent on the total price.
    }
    
    // --- Jalaali Date Formatting ---
    function formatToJalaliDisplay(gregorianDate) {
        if (!gregorianDate || isNaN(gregorianDate.getTime())) {
            return "تاریخ نامعتبر";
        }
        try {
            const year = gregorianDate.getFullYear();
            if (year < 1000 || year > 3000) {
                return "سال میلادی نامعتبر";
            }
            // Ensure jalaali object is available (loaded from CDN)
            if (typeof jalaali === 'undefined') {
                console.error('jalaali-js library is not loaded.');
                return 'خطا در بارگذاری کتابخانه تاریخ';
            }
            const jDate = jalaali.toJalaali(year, gregorianDate.getMonth() + 1, gregorianDate.getDate());
            return `${jDate.jy}/${String(jDate.jm).padStart(2, '0')}/${String(jDate.jd).padStart(2, '0')}`;
        } catch (e) {
            console.error("Error converting to Jalali:", e, "Input Gregorian Date:", gregorianDate);
            return "خطا در تبدیل تاریخ";
        }
    }

    // --- Message Display ---
    function displayMessage(message, type = 'error') {
        if (!messageArea) return;
        messageArea.innerHTML = `<div class="${type === 'error' ? 'error-message' : (type === 'info' ? 'info-message' : 'success-message')}">${message}</div>`;
        if (messageArea.firstElementChild) {
            messageArea.firstElementChild.style.animation = 'none';
            messageArea.firstElementChild.offsetHeight;
            messageArea.firstElementChild.style.animation = null;
        }
        if (type === 'error' && resultsArea) {
            // resultsArea.classList.add('hidden'); // Keep results visible even on some errors unless critical
        }
    }
    
    // --- Animation Helpers ---
    function animateIn(element) {
        if (!element) return;
        element.classList.remove('hidden');
        element.style.animation = 'none';
        element.offsetHeight; /* trigger reflow */
        element.style.animation = 'fadeInSlideUpStep 0.6s ease-out forwards';
    }

    function animateOut(element, callback) {
        if (!element) { if (callback) callback(); return; }
        element.style.animation = 'fadeOutSlideDownStep 0.4s ease-in forwards';
        setTimeout(() => {
            element.classList.add('hidden');
            element.style.animation = ''; 
            if (callback) callback();
        }, 400); 
    }
    
    // --- Calculate Button Event Listener ---
    if (calculateButton) {
        calculateButton.addEventListener('click', function () {
            if (messageArea) messageArea.innerHTML = '';
            if (resultsArea) resultsArea.classList.add('hidden');
            if (proceedToUploadButton) proceedToUploadButton.classList.add('hidden');
            if (installmentsTableBody) installmentsTableBody.innerHTML = '';

            const totalPrice = parseFloat(hiddenTotalPriceInput.value);
            const downPaymentRaw = parseFloat(hiddenDownPaymentInput.value); // This value is now auto-calculated
            const numInstallments = parseInt(numInstallmentsSelect.value);

            const todayGregorian = new Date();
            let firstInstallmentGregorianDate = new Date(todayGregorian.getFullYear(), todayGregorian.getMonth() + 1, todayGregorian.getDate());
            if (firstInstallmentGregorianDate.getDate() !== todayGregorian.getDate()) {
                firstInstallmentGregorianDate = new Date(todayGregorian.getFullYear(), todayGregorian.getMonth() + 2, 0);
            }
            
            if(startDateDisplayInfo) startDateDisplayInfo.textContent = formatToJalaliDisplay(new Date(firstInstallmentGregorianDate.getTime()));

            const installmentIntervalMonths = 1;

            // --- Validations ---
            if (isNaN(totalPrice) || totalPrice <= 0) { displayMessage('لطفاً مبلغ کل کالا را به درستی انتخاب کنید.'); return; }
            if (isNaN(downPaymentRaw) || downPaymentRaw < 0) { displayMessage('مبلغ پیش پرداخت محاسبه شده نامعتبر است.'); return; } // Should not happen if auto-calculated
            if (downPaymentRaw > totalPrice) { displayMessage('مبلغ پیش پرداخت نمی‌تواند بیشتر از مبلغ کل باشد.'); return; } // Should not happen if 30%
            if (isNaN(numInstallments) || numInstallments <= 0) { displayMessage('تعداد اقساط را به درستی انتخاب کنید.'); return; }
            if (!firstInstallmentGregorianDate || isNaN(firstInstallmentGregorianDate.getTime())) { displayMessage('خطا در محاسبه تاریخ شروع اقساط.'); return; }

            const remainingAmount = totalPrice - downPaymentRaw;
            const totalPayable = remainingAmount;
            let baseInstallmentAmount = 0;
            let lastInstallmentAmount = 0;
            let calculatedInstallmentsSchedule = [];


            if (totalPayable > 0 && numInstallments > 0) {
                baseInstallmentAmount = Math.ceil((totalPayable / numInstallments) / 100000) * 100000;
                let sumOfEarlyInstallments = 0;
                if (numInstallments > 1) {
                    for (let k = 0; k < numInstallments - 1; k++) {
                        sumOfEarlyInstallments += baseInstallmentAmount;
                    }
                }
                lastInstallmentAmount = (totalPayable - sumOfEarlyInstallments);
                if (numInstallments === 1) {
                    baseInstallmentAmount = totalPayable;
                    lastInstallmentAmount = totalPayable;
                } else if (lastInstallmentAmount < 0 && baseInstallmentAmount > 0) {
                    lastInstallmentAmount = Math.max(0, lastInstallmentAmount);
                }
            } else if (totalPayable === 0) { // This case means downPaymentRaw === totalPrice
                baseInstallmentAmount = 0;
                lastInstallmentAmount = 0;
            }


            const totalDurationMonths = numInstallments > 0 ? numInstallments * installmentIntervalMonths : 0;

            // Update results display
            if(document.getElementById('resultTotalPriceDisplayWp')) document.getElementById('resultTotalPriceDisplayWp').textContent = formatNumber(totalPrice);
            if(document.getElementById('resultDownPaymentDisplayWp')) document.getElementById('resultDownPaymentDisplayWp').textContent = formatNumber(downPaymentRaw);
            if(document.getElementById('remainingAmountDisplayWp')) document.getElementById('remainingAmountDisplayWp').textContent = formatNumber(totalPayable);
            if(document.getElementById('baseInstallmentAmountDisplayWp')) document.getElementById('baseInstallmentAmountDisplayWp').textContent = formatNumber(baseInstallmentAmount);
            if(document.getElementById('totalDurationDisplayWp')) document.getElementById('totalDurationDisplayWp').textContent = formatNumber(totalDurationMonths);
            
            if(resultsArea) animateIn(resultsArea);
            if(proceedToUploadButton) proceedToUploadButton.classList.remove('hidden');
            
            if (totalPayable >= 0) {
                displayMessage('محاسبات با موفقیت انجام شد. لطفاً برای ذخیره، ادامه دهید.', 'success');
            }

            // Populate table
            if (installmentsTableBody) {
                installmentsTableBody.innerHTML = '';
                 if (totalPayable > 0 && numInstallments > 0) {
                    const firstInstallmentJalali = jalaali.toJalaali(firstInstallmentGregorianDate.getFullYear(), firstInstallmentGregorianDate.getMonth() + 1, firstInstallmentGregorianDate.getDate());
                    for (let i = 0; i < numInstallments; i++) {
                        const row = installmentsTableBody.insertRow();
                        row.insertCell().textContent = formatNumber(i + 1);
                        
                        const dateCell = row.insertCell();
                        let currentJYear = firstInstallmentJalali.jy;
                        let currentJMonth = firstInstallmentJalali.jm + i;
                        let targetJDay = firstInstallmentJalali.jd;
                        while (currentJMonth > 12) {
                            currentJMonth -= 12;
                            currentJYear += 1;
                        }
                        const daysInCurrentJMonth = jalaali.jalaaliMonthLength(currentJYear, currentJMonth);
                        if (targetJDay > daysInCurrentJMonth) {
                            targetJDay = daysInCurrentJMonth;
                        }
                        const gregorianParts = jalaali.toGregorian(currentJYear, currentJMonth, targetJDay);
                        let effectiveDate = new Date(gregorianParts.gy, gregorianParts.gm - 1, gregorianParts.gd);
                        const jalaliDateStr = formatToJalaliDisplay(effectiveDate);
                        dateCell.textContent = jalaliDateStr;
                        
                        const amountCell = row.insertCell();
                        const currentAmount = (i === numInstallments - 1) ? lastInstallmentAmount : baseInstallmentAmount;
                        amountCell.textContent = formatNumber(currentAmount);

                        calculatedInstallmentsSchedule.push({
                            date: jalaliDateStr,
                            amount: currentAmount
                        });

                        Array.from(row.cells).forEach(cell => cell.className = 'px-4 py-2 whitespace-nowrap text-sm table-cell-custom');
                    }
                } else if (totalPayable === 0 && numInstallments > 0) {
                    const row = installmentsTableBody.insertRow();
                    const cell = row.insertCell();
                    cell.colSpan = 3;
                    cell.textContent = "مبلغ به طور کامل پرداخت شده است، نیازی به اقساط نیست.";
                    cell.className = 'px-4 py-3 text-center text-sm text-gray-600';
                }
            }

            // Prepare data for AJAX submission (Step 1 data)
            const formData = new FormData();
            formData.append('action', 'cic_handle_calculation');
            formData.append('nonce', cic_ajax_object.nonce);
            formData.append('total_price', totalPrice);
            formData.append('down_payment', downPaymentRaw);
            formData.append('num_installments', numInstallments);
            formData.append('remaining_amount', totalPayable);
            formData.append('base_installment', baseInstallmentAmount);
            formData.append('last_installment', lastInstallmentAmount);
            formData.append('total_duration', totalDurationMonths);
            formData.append('first_installment_date_jalali', formatToJalaliDisplay(firstInstallmentGregorianDate));
            formData.append('installments_schedule', JSON.stringify(calculatedInstallmentsSchedule));


            // Send data to WordPress via AJAX
            fetch(cic_ajax_object.ajax_url, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    displayMessage(data.data.message, 'success');
                    if (submissionIdInput && data.data.submission_id) {
                        submissionIdInput.value = data.data.submission_id; // Store submission ID for step 2
                    }
                    if (proceedToUploadButton) proceedToUploadButton.classList.remove('hidden');
                } else {
                    displayMessage('خطا در ذخیره سازی: ' + (data.data.message || 'خطای ناشناخته'), 'error');
                }
            })
            .catch(error => {
                console.error('Error during AJAX submission:', error);
                displayMessage('خطا در ارتباط با سرور.', 'error');
            });

        });
    }
    
    // --- Navigation Button Event Listeners ---
    if (proceedToUploadButton && formStep1 && formStep2) {
        proceedToUploadButton.addEventListener('click', function () {
            animateOut(formStep1, () => {
                animateIn(formStep2);
            });
             if (resultsArea) animateOut(resultsArea); // Hide results area when moving to step 2
        });
    }

    if (prevButton && formStep1 && formStep2) {
        prevButton.addEventListener('click', function () {
            animateOut(formStep2, () => {
                animateIn(formStep1);
                if (resultsArea && document.getElementById('installmentsTableBodyWp').rows.length > 0) {
                   animateIn(resultsArea); // Re-show results if they were calculated
                }
            });
        });
    }

    // --- Document Submission (Placeholder) ---
    if (submitDocumentsButton) {
        submitDocumentsButton.addEventListener('click', function () {
            if (!chequeImageInput.files || chequeImageInput.files.length === 0) {
                displayMessage('لطفاً تصویر چک را انتخاب کنید.', 'error');
                return;
            }
            if (!nationalIdImageInput.files || nationalIdImageInput.files.length === 0) {
                displayMessage('لطفاً تصویر کارت ملی را انتخاب کنید.', 'error');
                return;
            }

            const currentSubmissionId = submissionIdInput ? submissionIdInput.value : null;
            if (!currentSubmissionId) {
                displayMessage('خطا: شناسه محاسبه برای ارسال مدارک یافت نشد. لطفاً ابتدا محاسبه را انجام دهید.', 'error');
                return;
            }

            displayMessage('در حال آماده سازی برای ارسال مدارک...', 'info');
            
            const docFormData = new FormData();
            docFormData.append('action', 'cic_handle_document_upload');
            docFormData.append('nonce', cic_ajax_object.nonce); // Consider a different nonce for uploads for better security
            docFormData.append('submission_id', currentSubmissionId);
            if (chequeImageInput.files[0]) {
                docFormData.append('cheque_image_wp', chequeImageInput.files[0]);
            }
            if (nationalIdImageInput.files[0]) {
                docFormData.append('national_id_image_wp', nationalIdImageInput.files[0]);
            }

            // AJAX for document upload (placeholder for actual upload logic)
            fetch(cic_ajax_object.ajax_url, {
                method: 'POST',
                body: docFormData 
                // Note: For file uploads with fetch, do not set Content-Type header manually.
                // The browser will set it correctly with the boundary.
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    displayMessage('مدارک با موفقیت ارسال شد (شبیه‌سازی شده). ' + (data.data.message || ''), 'success');
                } else {
                    displayMessage('خطا در ارسال مدارک: ' + (data.data.message || 'خطای ناشناخته'), 'error');
                }
            })
            .catch(error => {
                console.error('Error during document AJAX submission:', error);
                displayMessage('خطا در ارتباط با سرور هنگام ارسال مدارک.', 'error');
            });
        });
    }
    
    // --- Image Preview Handlers ---
    function handleImagePreview(inputElement, previewElement) {
        if (inputElement && previewElement) {
            inputElement.addEventListener('change', function (event) {
                if (event.target.files && event.target.files[0]) {
                    const reader = new FileReader();
                    reader.onload = function (e) {
                        previewElement.src = e.target.result;
                        previewElement.classList.remove('hidden');
                    }
                    reader.readAsDataURL(event.target.files[0]);
                } else {
                    previewElement.src = "#";
                    previewElement.classList.add('hidden');
                }
            });
        }
    }
    handleImagePreview(chequeImageInput, chequePreview);
    handleImagePreview(nationalIdImageInput, nationalIdPreview);

    // --- Reset Button Event Listener ---
    if (resetButton) {
        resetButton.addEventListener('click', function () {
            if(totalPriceSlider) {
                totalPriceSlider.value = "5000000";
                // Trigger the update to also reset down payment
                updateTotalAndDownPaymentDisplay(totalPriceSlider.value);
            }
            
            if(numInstallmentsSelect) numInstallmentsSelect.value = "2";
            
            if(messageArea) messageArea.innerHTML = '';
            if(resultsArea) resultsArea.classList.add('hidden');
            if(proceedToUploadButton) proceedToUploadButton.classList.add('hidden');
            if(installmentsTableBody) installmentsTableBody.innerHTML = '';
            if(startDateDisplayInfo) startDateDisplayInfo.textContent = '';
            if(submissionIdInput) submissionIdInput.value = '';


            if(formStep2) formStep2.classList.add('hidden');
            if(formStep1) {
                 formStep1.classList.remove('hidden');
                 animateIn(formStep1); 
            }

            if(chequeImageInput) chequeImageInput.value = '';
            if(nationalIdImageInput) nationalIdImageInput.value = '';
            if(chequePreview) { chequePreview.src = "#"; chequePreview.classList.add('hidden');}
            if(nationalIdPreview) { nationalIdPreview.src = "#"; nationalIdPreview.classList.add('hidden');}

            displayMessage('فرم پاک شد. لطفاً مقادیر جدید را وارد کنید.', 'success');
        });
    }
});
