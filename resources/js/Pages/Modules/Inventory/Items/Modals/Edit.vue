<template>
    <b-modal v-model="showModal" style="--vz-modal-width: 600px;" header-class="p-3 bg-light" title="Edit Item" class="v-modal-custom" modal-class="zoomIn" centered no-close-on-backdrop>
        <form class="customform">
            <BRow class="g-3 mt-1">
                <BCol lg="12" class="mt-1 mb-n1">
                    <InputLabel for="name" value="Name" :message="form.errors.name"/>
                    <TextInput id="name" v-model="form.name" type="text" class="form-control" autofocus placeholder="Please enter item name" autocomplete="name" required :class="{ 'is-invalid': form.errors.name }" readonly @input="handleInput('name')" :light="true"/>
                </BCol>
                <BCol lg="6" class="mt-1">
                    <InputLabel for="category_id" value="Category" :message="form.errors.category_id"/>
                    <Multiselect :options="dropdowns.categories" label="name" :searchable="true" v-model="form.category_id" :message="form.errors.category_id" placeholder="Select Category"/>
                </BCol>
                <BCol lg="6" class="mt-1">
                    <InputLabel for="category_id" value="Reorder point" :message="form.errors.reorder"/>
                    <div class="input-group mb-1">
                        <input type="text" v-model="form.reorder" placeholder="Size" class="form-control" style="height: 39px; width: 50%; background-color: #f5f6f7;">
                        <select v-model="form.unit_id" class="form-select" id="inputGroupSelect02" style="height: 39px; width: 50%; background-color: #f5f6f7;" disabled>
                            <option :value="null" selected>Select</option>
                            <option :value="list.value" v-for="list in dropdowns.units" v-bind:key="list.value">{{list.name}}</option>
                        </select>
                    </div>
                </BCol>
            </BRow>
        </form>
        <template v-slot:footer>
            <b-button @click="hide()" variant="light" block>Cancel</b-button>
            <b-button @click="submit('ok')" variant="primary" :disabled="form.processing" block>Submit</b-button>
        </template>
    </b-modal>
</template>
<script>
import _ from 'lodash';
import { useForm } from '@inertiajs/vue3';
import Multiselect from "@vueform/multiselect";
import "@vueform/multiselect/themes/default.css";
import InputLabel from '@/Shared/Components/Forms/InputLabel.vue';
import TextInput from '@/Shared/Components/Forms/TextInput.vue';
export default {
    components: { InputLabel, TextInput, Multiselect },
    props: ['dropdowns'],
    data(){
        return {
            currentUrl: window.location.origin,
            form: useForm({
                id: null,
                name: null,
                reorder: null,
                category_id: null,
                supplier: null,
                unit_id: null,
                option: 'item'
            }),
            showModal: false,
        }
    },
    methods: { 
        show(data){
            this.form.id = data.id;
            this.form.name = data.name;
            this.form.reorder = data.reorder;
            this.form.category_id = data.category_id;
            this.form.unit_id = data.unit_id;
            this.showModal = true;
            this.form.errors = [];
        },
        submit(){
            this.form.put('/inventory/update',{
                preserveScroll: true,
                onSuccess: (response) => {
                    this.$emit('update',this.$page.props.flash.data);
                    this.form.reset();
                    this.hide();
                }
            });
        },
        handleInput(field) {
            this.form.errors[field] = false;
        },
        hide(){
            this.showModal = false;
        }
    }
}
</script>