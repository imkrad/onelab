<template lang="">
    <Head title="Technical Service Requests"/>
    <PageHeader title="Technical Service Requests" pageTitle="Menu" />
    <div class="chat-wrapper d-lg-flex gap-1 mx-n4 mt-n4 p-1">
        <div class="file-manager-content w-100 p-4 pb-0" style="height: calc(100vh - 180px); overflow: auto;" ref="box">
            <Lists :dropdowns="dropdowns" :counts="counts"/>
        </div>
    </div>
</template>
<script>
import Lists from './Components/Lists.vue';
import PageHeader from '@/Shared/Components/PageHeader.vue';
export default {
    components: { PageHeader, Lists },
    props: ['dropdowns','counts']
}
</script>