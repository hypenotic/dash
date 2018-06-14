//actions are dispatched to have vuex do something (eg. pull data)
//while data is being actioned upon, a commit is sent to the setters in mutations to change the state

import axios from 'axios';

const SET_MENU     = 'SET_MENU';
const SET_OVERLAY     = 'SET_OVERLAY';
const SET_VOTE_STATUS     = 'SET_VOTE_STATUS';
const SET_USER_ID     = 'SET_USER_ID';

const actions = {
    GET_MENU: ({ commit, dispatch }) => {
        console.log('GET_MENU dispatched');
        axios.get('http://vote.hypenotic.com/cms/wp-json/wp-api-menus/v2/menus/2')
            .then(function (response) {
            commit(SET_MENU, response.data);
        })
            .catch(function (error) {
            console.log(error)
        })
    },
    GET_OVERLAY_STATUS: ({ commit, dispatch }, status) => {
        console.log('GET_OVERLAY dispatched', status);
        if (status == false) {
            commit(SET_OVERLAY, false);
        } else {
            commit(SET_OVERLAY, true);
        }
    },
    CHECK_USER: ({ commit, dispatch }) => {
        console.log('CHECK_USER dispatched');
        let check = localStorage.getItem('localState');
        if (check !== null) {
            commit(SET_VOTE_STATUS, false);
            let info = JSON.parse(check);
            let id = info.userId;
            commit(SET_USER_ID, id);
        } else {
            console.log('localStorage empty');
            return;
        }
    },
}

export default actions;