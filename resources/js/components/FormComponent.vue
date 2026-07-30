<template>
    <div v-if="error" class="alert alert-danger mt-2" role="alert">
        {{ error }}
    </div>

    <div v-if="spamError" class="alert alert-warning mt-2" role="alert">
        {{ spamError }}
    </div>

    <div v-if="success" class="alert alert-success mt-2" role="alert">
        Thank you! Your data has been submitted.
    </div>

    <div class="row justify-content-center mt-2">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">Contact form</div>
                <div class="card-body">
                    <form method="POST" @submit.prevent="sendForm">
                        <div class="d-none" aria-hidden="true">
                            <label for="website">Leave this empty</label>
                            <input id="website" name="website" autocomplete="off" tabindex="-1" v-model="form.hiddenWebsite" />
                        </div>
                        <input type="hidden" v-model="form.submittedAt" />

                        <div class="form-group mb-3">
                            <label for="accountName">Name</label>
                            <input type="text" class="form-control" id="accountName" v-model="form.accountName" />
                            <span v-if="errors.accountName" class="text-danger">{{ errors.accountName }}</span>
                        </div>

                        <div class="form-group mb-3">
                            <label for="accountPhone">Phone</label>
                            <div class="input-group">
                                <span class="input-group-text">+380</span>
                                <input
                                    type="text"
                                    class="form-control"
                                    id="accountPhone"
                                    v-model="form.accountPhone"
                                    @input="onPhoneInput"
                                    placeholder="(67) 123-45-67"
                                />
                            </div>
                            <span v-if="errors.accountPhone" class="text-danger">{{ errors.accountPhone }}</span>
                        </div>

                        <button type="submit" class="btn btn-primary mt-2">Send</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { reactive, ref } from 'vue';
import axios from 'axios';
import * as yup from 'yup';

const error = ref('');
const success = ref(false);
const spamError = ref('');

const form = reactive({
    accountName: '',
    accountPhone: '',
    hiddenWebsite: '',
    submittedAt: Date.now(),
});

const errors = reactive({
    accountName: '',
    accountPhone: '',
});

const nameRegex = /^[\p{L}\s'-]+$/u;
const phoneDigitsPattern = /^(?:39|50|63|66|67|68|73|91|92|93|94|95|96|97|98|99)\d{7}$/;

const validationSchema = yup.object({
    accountName: yup
        .string()
        .trim()
        .required('Name is required')
        .test('name', 'Name must contain only letters', value => {
            const val = (value || '').trim();
            return val === '' || nameRegex.test(val);
        }),
    accountPhone: yup
        .string()
        .trim()
        .required('Phone is required')
        .transform(value => (value || '').replace(/\D/g, ''))
        .test('ukr-phone', 'Phone must be a valid Ukrainian mobile number', value => {
            const val = (value || '').trim();
            return val === '' || phoneDigitsPattern.test(val);
        }),
});

function resetErrors() {
    error.value = '';
    spamError.value = '';
    Object.keys(errors).forEach(key => {
        errors[key] = '';
    });
}

function resetForm() {
    form.accountName = '';
    form.accountPhone = '';
    form.hiddenWebsite = '';
    form.submittedAt = Date.now();
}

function isSpam() {
    if (form.hiddenWebsite.trim() !== '') {
        spamError.value = 'Spam detected. Please do not fill hidden fields.';
        return true;
    }

    const elapsed = Date.now() - form.submittedAt;
    if (elapsed < 2000) {
        spamError.value = 'Please wait a moment before submitting the form.';
        return true;
    }

    return false;
}

function formatPhone(value) {
    const digits = (value || '').replace(/\D/g, '');
    if (!digits) {
        return '';
    }

    let normalizedDigits = digits;
    
    // Remove 380 prefix if present
    if (digits.startsWith('380')) {
        normalizedDigits = digits.slice(3);
    } else if (digits.startsWith('0')) {
        // Remove leading 0
        normalizedDigits = digits.slice(1);
    }

    // Format as (XX) XXX-XX-XX
    const code = normalizedDigits.slice(0, 2);
    const part1 = normalizedDigits.slice(2, 5);
    const part2 = normalizedDigits.slice(5, 7);
    const part3 = normalizedDigits.slice(7, 9);

    let formatted = '';
    if (code) formatted += `(${code}`;
    if (part1) formatted += `) ${part1}`;
    if (part2) formatted += `-${part2}`;
    if (part3) formatted += `-${part3}`;
    
    return formatted;
}

function onPhoneInput(event) {
    form.accountPhone = formatPhone(event.target.value);
}

async function sendForm() {
    resetErrors();
    success.value = false;

    if (isSpam()) {
        return;
    }

    try {
        const validated = await validationSchema.validate(form, { abortEarly: false });

        const response = await axios.post('/api/send-contact-form', {
            accountName: validated.accountName,
            accountPhone: '+380' + validated.accountPhone.replace(/\D/g, ''),
            hiddenWebsite: form.hiddenWebsite,
            submittedAt: form.submittedAt,
        });

        if (response?.data?.status === 'success') {
            success.value = true;
            resetForm();
            return;
        }

        error.value = response?.data?.message || 'An unexpected error occurred.';
    } catch (validationError) {
        if (validationError.name === 'ValidationError') {
            validationError.inner.forEach(item => {
                if (item.path && errors[item.path] !== undefined) {
                    errors[item.path] = item.message;
                }
            });
            return;
        }

        if (validationError.response) {
            error.value = validationError.response.data?.message || 'An error occurred. Please try again.';
            return;
        }

        error.value = validationError.message || 'An unknown error occurred.';
    }
}
</script>
