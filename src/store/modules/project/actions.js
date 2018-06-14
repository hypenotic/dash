//actions are dispatched to have vuex do something (eg. pull data)
//while data is being actioned upon, a commit is sent to the setters in mutations to change the state

import axios from 'axios';

const setProjects     = 'setProjects';

const actions = {
    popProjects: ({ commit, dispatch }) => {
        console.log('dispatch popProjects');
        axios.get('http://hypedash.test/cms/wp-json/wp/v2/project')
            .then(function (response) {
            commit(setProjects, response.data);
        })
            .catch(function (error) {
            console.log(error)
        })
    },
    pushProject: ({ commit, dispatch }) => {
        console.log('dispatch pushProjects');
        axios.post('http://hypedash.test/cms/wp-json/wp/v2/project', {
            firstName: 'Fred',
            lastName: 'Flintstone'
        })
            .then(function (response) {
            commit(setProject, response.data);
        })
            .catch(function (error) {
            console.log(error);
        });
    }
}

export default actions;