<template>
    <b-modal v-model="showModal" style="--vz-modal-width: 1000px;" header-class="p-3 bg-light" title="Create Official Receipt" class="v-modal-custom" modal-class="zoomIn" centered no-close-on-backdrop>
        <form class="customform">
            <BRow class="g-3">
                <BCol lg="3" class="mt-1">
                    <InputLabel for="collection" value="Collection" :message="form.errors.collection_id"/>
                    <Multiselect 
                    :options="collections" 
                    v-model="or.collection_id"
                    label="name"
                    placeholder="Select Collection type"/>
                </BCol>
                <BCol lg="3 mt-1">
                    <InputLabel for="deposit" value="Deposit Type" :message="form.errors.deposit_id"/>
                    <Multiselect 
                    :options="deposits" 
                    v-model="or.deposit_id" 
                    label="name"
                    @input="handleInput('deposit_id')"
                    placeholder="Select Deposit type"/>
                </BCol>
                <BCol lg="3 mt-1">
                    <InputLabel for="orseries" value="O.R Series" :message="form.errors.orseries"/>
                    <Multiselect 
                    :options="orseries" 
                    v-model="or.orseries" 
                    object
                    label="name"
                    @input="handleInput('orseries')"
                    placeholder="Select OR"/>
                </BCol>
                <BCol lg="3" class="mt-1">
                    <InputLabel for="payment" value="Payment" :message="form.errors.payment_id"/>
                    <Multiselect 
                    :options="payments" 
                    v-model="or.payment"
                    object
                    label="name"
                    placeholder="Select Payment type"/>
                </BCol>
                <BCol lg="12" v-if="or.orseries">
                    <hr class="text-muted mt-0"/>
                    <div class="d-grid gap-2" >
                        <b-button variant="success">O.R # : {{or.orseries.next}}</b-button>
                    </div>
                </BCol>
                <BCol lg="12 mt-1 mb-n3">
                    <hr class="text-muted"/>
                </BCol>
                <template v-if="or.payment">
                    <template v-if="or.payment.name == 'Cheque' || or.payment.name == 'Online Transfer' || or.payment.name == 'Bank Deposit'">
                        <BCol lg="12" class="mb-n3" v-if="or.payment.name == 'Bank Deposit'">
                            <BRow class="g-3">
                                <BCol lg="8"  style="margin-top: 13px; margin-bottom: -12px;" class="fs-12">Please choose type of deposit?</BCol>
                                <BCol lg="4"  style="margin-top: 13px; margin-bottom: -12px;">
                                <div class="row">
                                        <div class="col-md-6">
                                            <div class="custom-control custom-radio mb-3">
                                                <input type="radio" id="customRadio1" class="custom-control-input me-2" :value="false" v-model="details.is_cheque">
                                                <label class="custom-control-label fw-normal fs-12" for="customRadio1">Cash</label>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="custom-control custom-radio mb-3">
                                                <input type="radio" id="customRadio2" class="custom-control-input me-2" :value="true" v-model="details.is_cheque">
                                                <label class="custom-control-label fw-normal fs-12" for="customRadio2">Cheque</label>
                                            </div>
                                        </div>
                                    </div>
                                </BCol>
                                <BCol lg="12"><hr class="text-muted mt-n2"/></BCol>
                            </BRow>
                        </BCol>
                        <BCol lg="3 mt-1">
                            <InputLabel :value="or.payment.others" :message="form.errors.number"/>
                            <TextInput v-model="details.number" type="text" class="form-control" @input="handleInput('number')" :light="true"/>
                        </BCol>
                        <BCol lg="3 mt-1">
                            <InputLabel value="Date" :message="form.errors.date_at"/>
                            <TextInput v-model="details.date_at" type="date" class="form-control" @input="handleInput('date')" :light="true"/>
                        </BCol>
                        <BCol lg="3 mt-1">
                            <InputLabel value="Amount" :message="form.errors.amount"/>
                            <Amount @amount="amount" ref="testing" :readonly="false" @input="handleInput('amount')"/>
                        </BCol>
                        <BCol lg="3 mt-1">
                            <InputLabel value="Bank Name" :message="form.errors.bank"/>
                            <TextInput v-model="bank" type="text" class="form-control" @input="handleInput('bank')" :light="true"/>
                        </BCol>
                        <BCol lg="12 mt-n1 mb-n3">
                            <hr class="text-muted"/>
                        </BCol>
                    </template>
                </template>
                <BCol lg="12" class="mb-n3" v-if="or.collection_id">
                    <BRow class="g-3">
                        <BCol lg="7"  style="margin-top: 13px; margin-bottom: -12px;" class="fs-12">Please choose type of payor?</BCol>
                        <BCol lg="5"  style="margin-top: 13px; margin-bottom: -12px;">
                        <div class="row">
                                <div class="col-md-6">
                                    <div class="custom-control custom-radio mb-3">
                                        <input type="radio" id="customRadio1" class="custom-control-input me-2" value="external" v-model="payor_type">
                                        <label class="custom-control-label fw-normal fs-12" for="customRadio1">External </label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="custom-control custom-radio mb-3">
                                        <input type="radio" id="customRadio2" class="custom-control-input me-2" value="internal" v-model="payor_type">
                                        <label class="custom-control-label fw-normal fs-12" for="customRadio2">Internal</label>
                                    </div>
                                </div>
                            </div>
                        </BCol>
                        <BCol lg="12"><hr class="text-muted mt-n2"/></BCol>
                    </BRow>
                </BCol>
                <BCol lg="12" v-if="payor_type" class="mt-1">
                    <InputLabel for="customer" value="Payor" :message="form.errors.customer"/>
                    <Multiselect 
                    :options="customers" 
                    @search-change="fetchCustomer" 
                    v-model="form.customer" 
                    object label="name"
                    :searchable="true" 
                    @input="handleInput('customer')"
                    placeholder="Select Payor"/>
                </BCol>
            </BRow>
        </form>
        <template v-slot:footer>
            <b-button @click="hide()" variant="light" block>Close</b-button>
            <b-button @click="submit('ok')" variant="primary" :disabled="form.processing" block>Submit</b-button>
        </template>
    </b-modal>
</template>
<script>
import TextInput from '@/Shared/Components/Forms/TextInput.vue';
import InputLabel from '@/Shared/Components/Forms/InputLabel.vue';
import Multiselect from "@vueform/multiselect";
import Amount from '@/Shared/Components/Forms/Amount.vue';
export default {
    components: { Multiselect, InputLabel, TextInput, Amount },
    props: ['deposits','orseries','collections','payments'],
    data(){
        return {
            currentUrl: window.location.origin,
            or: {
                id: null,
                collection_id: null,
                payment: null,
                deposit_id: null,
                orseries: null,
                total: null,
                type: null,
                option: 'receipt_nonlab'
            },
            details: {
                type: null,
                number: null,
                bank: null,
                date_at: null,
                amount: null,
                is_cheque: false,
            },
            form : {
                customer: null,
                errors: []
            },
            payor_type: null,
            type: null,
            customers: [],
            customer: null,
            showModal: false
        }
    },
    watch: {
        "or.payment"(newVal){
            if(newVal){
                this.type = newVal.name;
            }else{
                this.type = null;
            }
        },
    },
    methods: { 
        show(){
            this.showModal = true;
        },
        fetchCustomer(code){
            axios.get('/finance',{
                params: {
                    option: 'payor',
                    type: this.payor_type,
                    keyword: code
                }
            })
            .then(response => {
                this.customers = response.data;
            })
            .catch(err => console.log(err));
        },
        submit(){
            if(this.type === 'Cheque' || this.type === 'Online Transfer' || this.type === 'Bank Deposit'){
                this.form = this.$inertia.form({
                    'customer_id': (this.form.customer) ? this.form.customer.value : null,
                    'collection_id': this.or.collection_id,
                    'payment_id': (this.or.payment) ? this.or.payment.value : null,
                    'deposit_id': this.or.deposit_id,
                    'orseries': this.or.orseries,
                    'orseries_id': (this.or.orseries) ? this.or.orseries.value : null,
                    'details_number': this.details.number,
                    'details_date_at': this.details.date_at,
                    'details_bank': this.details.bank,
                    'details_amount': this.details.amount,
                    'details_is_cheque': this.details.is_cheque,
                    'selected': this.or.selected,
                    'total': this.or.total,
                    'type': this.type,
                    'option': 'receipt_nonlab'
                });
            }else{
                this.form = this.$inertia.form({
                    'customer_id': (this.form.customer) ? this.form.customer.value : null,
                    'collection_id': this.or.collection_id,
                    'payment_id': (this.or.payment) ? this.or.payment.value : null,
                    'deposit_id': this.or.deposit_id,
                    'orseries_id': (this.or.orseries) ? this.or.orseries.value : null,
                    'orseries': this.or.orseries,
                    'type': this.type,
                    'selected': this.or.selected,
                    'total': this.or.total,
                    'option': 'receipt_nonlab'
                });
            }

            this.form.post('/finance',{
                preserveScroll: true,
                onSuccess: (response) => {
                    this.$emit('update',true);
                    this.hide();
                },
            });
        },
        amount(val){
            this.details.amount = val;
        },
        total(val){
            this.or.total = val;
        },
        handleInput(field) {
            this.form.errors[field] = false;
        },
        hide(){
            this.or.id = null;
            this.or.deposit_id = null;
            this.or.orseries = null;
            this.or.selected = {payment:{}};
            this.or.total = null;
            this.or.type = null;
            this.details.type = null;
            this.details.number = null;
            this.details.bank = null;
            this.details.date_at = null;
            this.details.amount = null;
            this.showModal = false;
        }
    }
}
</script>