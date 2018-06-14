import Vue from 'vue'
import Vuex from 'vuex'
import store from './store'
import App from './app'
import axios from 'axios'

Vue.use(axios)
Vue.use(Vuex)

Vue.config.productionTip = false

/* eslint-disable no-new */
var app = new Vue({
    el: '#app',
    //router,
    store,
    render: h => h(App)
});
