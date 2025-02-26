<template>
    <b-row class="g-2 mb-2 mt-n2">
        <b-col lg>
            <div class="input-group mb-1">
                <span class="input-group-text"> <i class="ri-search-line search-icon"></i></span>
                <input type="text" v-model="searchTerm" @input="search" placeholder="Search Supplier" class="form-control" style="width: 75%;">
                <select v-model="filter.is_active" @change="fetch()" class="form-select" id="inputGroupSelect01">
                    <option :value="1" selected>Active</option>
                    <option :value="0" selected>Inactive</option>
                </select>
                <span @click="refresh" class="input-group-text" v-b-tooltip.hover title="Refresh" style="cursor: pointer;"> 
                    <i class="bx bx-refresh search-icon"></i>
                </span>
                <b-button type="button" variant="primary" @click="openCreate">
                    <i class="ri-add-circle-fill align-bottom me-1"></i> Create
                </b-button>
            </div>
        </b-col>
    </b-row>
    <div class="table-responsive">
        <table class="table table-nowrap align-middle mb-0">
            <thead class="table-light">
                <tr class="fs-11">
                    <th style="width: 25%;">Name</th>
                    <th style="width: 15%;" class="text-center">Code</th>
                    <th style="width: 20%;" class="text-center">Serial no.</th>
                    <th style="width: 23%;" class="text-center">Laboratory</th>
                    <th style="width: 10%;" class="text-center">Status</th>
                    <th style="width: 7%;"></th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="(list,index) in lists" v-bind:key="index">
                    <td>
                        <h5 class="fs-13 mb-0 text-dark">{{list.name}}</h5>
                        <p class="fs-12 text-muted mb-0">{{list.supplier}}</p>
                    </td>
                    <td class="text-center fs-12">{{list.code}}</td>
                    <td class="text-center fs-12">{{list.serial_no}}</td>
                    <td class="text-center fs-12">{{list.laboratory}}</td>
                    <td class="text-center">
                        <span v-if="list.is_operational" class="badge bg-success">Operational</span>
                        <span v-else class="badge bg-danger">Inoperable</span>
                    </td>
                    <td class="text-end">
                        <b-button @click="openEdit(list,index)" class="me-1" variant="soft-warning" v-b-tooltip.hover title="Edit" size="sm">
                            <i class="ri-pencil-fill align-bottom"></i>
                        </b-button>
                        <b-button variant="soft-info" v-b-tooltip.hover title="View" size="sm">
                            <i class="ri-eye-fill align-bottom"></i>
                        </b-button>
                    </td>
                </tr>
            </tbody>
        </table>
        <Pagination class="ms-2 me-2" v-if="meta" @fetch="fetch" :lists="lists.length" :links="links" :pagination="meta" />
    </div>
    <Create @message="fetch()" :dropdowns="dropdowns" ref="create"/>
</template>
<script>
import _ from 'lodash';
import Create from '../Modals/Create.vue';
import simplebar from 'simplebar-vue';
import Pagination from "@/Shared/Components/Pagination.vue";
export default {
    props: ['dropdowns'],
    components: { Pagination, Create, simplebar },
    data(){
        return {
            currentUrl: window.location.origin,
            lists: [],
            meta: {},
            links: {},
            index: null,
            filter: {
                keyword: null,
                is_active: 1,
            },
            searchTerm: '',
            matchedRowIndex: null,
        }
    },
    watch: {
        "filter.keyword"(newVal){
            this.checkSearchStr(newVal)
        }
    },
    created(){
        this.fetch();
    },
    methods: {
        checkSearchStr: _.debounce(function(string) {
            this.fetch();
        }, 300),
        fetch(page_url){
            page_url = page_url || '/inventory';
            axios.get(page_url,{
                params : {
                    keyword: this.filter.keyword,
                    count: ((window.innerHeight-350)/58),
                    option: 'equipments'
                }
            })
            .then(response => {
                this.lists = response.data.data;
                this.meta = response.data.meta;
                this.links = response.data.links;     
            })
            .catch(err => console.log(err));
        },
        openCreate(){
            this.editable = false;
            this.$refs.create.show();
        },
        openEdit(data,index){
            this.index = index;
            this.editable = true;
            this.$refs.create.edit(data);
        },
        pushNew(data){
            if(this.editable){
                this.lists[this.index] = data.data;
            }else{
                this.lists.push(data.data);
            }
        },
        search() {
            const searchTerm = this.searchTerm.toLowerCase();
            const matchedIndices = this.lists.reduce((indices, list, index) => {
                if (list.name.toLowerCase().includes(searchTerm)) {
                    indices.push(index);
                }
                return indices;
            }, []);
            if (matchedIndices.length > 0 && searchTerm !== '') {
                const closestIndex = matchedIndices.reduce((closest, currentIndex) => {
                    const closestDistance = Math.abs(closest - searchTerm.length);
                    const currentDistance = Math.abs(currentIndex - searchTerm.length);
                    return currentDistance < closestDistance ? currentIndex : closest;
                }, matchedIndices[0]);

                this.matchedRowIndex = closestIndex;

                const rowId = 'row-' + closestIndex;
                const matchedRow = document.getElementById(rowId);

                if(matchedRow){
                    matchedRow.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            }else {
                this.matchedRowIndex = null;
            }
        },
    }
}
</script>
<style>
.thead-fixed {
   position: sticky;
   top: 0;
   background-color: #fff; /* Set the background color for the fixed header */
   z-index: 1; /* Ensure the fixed header is above the scrolling content */
}
</style>