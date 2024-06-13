import {useStore} from "vuex";

class Preference {
    constructor(http, store) {
        this.http = http;
        this.store = store;
        this.url = cp_url('preferences/js');
    }

    all() {
        return this.store.state.statamic.config.user.preferences;
    }

    get(key, fallback) {
        return data_get(this.all(), key, fallback);
    }

    set(key, value) {
        return this.commitOnSuccessAndReturnPromise(
            this.http.post(this.url, {key, value})
        );
    }

    append(key, value) {
        return this.commitOnSuccessAndReturnPromise(
            this.http.post(this.url, {key, value, append: true})
        );
    }

    remove(key, value=null, cleanup=true) {
        return this.commitOnSuccessAndReturnPromise(
            this.http.delete(`${this.url}/${key}`, { data: { value, cleanup } })
        );
    }

    removeValue(key, value) {
        return this.remove(key, value);
    }

    commitOnSuccessAndReturnPromise(promise) {
        promise.then(response => {
            this.store.commit('statamic/preferences', response.data);
        });

        return promise;
    }
}

export default Preference;
