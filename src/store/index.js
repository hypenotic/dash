import Vuex from 'vuex'

import moduleUser from './modules/user'
import moduleProject from './modules/project'
import moduleClient from './modules/client'

const store = () => {
    return new Vuex.Store({
        modules: {
            moduleUser,
            moduleProject,
            moduleClient,
        }
    })
}

export default store