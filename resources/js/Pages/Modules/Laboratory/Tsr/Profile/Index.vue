<template lang="">
    <Head title="Technical Service Requests"/>
    <div class="auth-page-wrapper d-flex min-vh-100">
        <div class="auth-page-content">
            <Top :selected="tsr.data" :analyses="analyses.data.length"/>
            <div class="chat-wrapper d-lg-flex gap-1 mx-n4 mt-n4 p-1">
                <div class="file-manager-content w-100 p-4 pb-0" ref="box" style="margin-left: 20px;">
                    <Main v-if="!tsr.data.is_shelf" :selected="tsr.data" :services="services" :analyses="analyses.data"/>  
                    <Shelf v-else :selected="tsr.data" :services="services" :analyses="analyses.data" :laboratories="laboratories"/>
                </div>
                <div class="file-manager-sidebar" style="margin-right: 20px; overflow-y: auto; overflow-x: hidden;">
                    <Sidebar :selected="tsr.data"/>
                </div>
            </div>
        </div>
    </div>
    <Message ref="message"/>
</template>
<script>
import Top from './Components/Top.vue';
import simplebar from "simplebar-vue";
import Main from './Components/Main.vue';
import Shelf from './Components/Shelf.vue';
import Message from './Modals/Message.vue';
import Sidebar from './Components/Sidebar.vue';
import PageHeader from '@/Shared/Components/PageHeader.vue';
export default {
    layout: null,
    components: { PageHeader, Top, Main, Sidebar, simplebar, Message, Shelf },
    props: ['tsr','services','analyses','laboratories'],
}
</script>
<style scoped>
.auth-page-wrapper .auth-page-content {
    padding-bottom: 0px;
  width: 100%;
  overflow: hidden;
  background-color: #f3f3f9;
}
.file-manager-sidebar {
  min-width: 24%;
  max-width: 24%;
  height: calc(100vh - 92px);
}
</style>
