Vue.component('feeder-card', {
    props: ['feeder', 'type'],
    computed: {
        feederData() {
            return this.type?.[this.feeder] || {};
        }
    },
    watch: {
        type: {
            handler(newVal) {
               
            },
            deep: true
        }
    },
    template: `
    <div v-if="feederData.title" class="w3-card-4 w3-border w3-padding w3-margin-bottom">
        <div class="w3-row">
            <div class="w3-col l12 w3-center">
                <strong>{{ feederData.title }}</strong>
            </div>
        </div>
        <hr>
        <div class="w3-row">
            <div class="w3-col l6">
                <div v-for="v in feederData.voltages" class="w3-row">
                    <div class="w3-col l4">{{ v.label }}</div>
                    <div class="w3-col l8">
                        <input type="text" class="readonly-textbox" :value="v.value" readonly>
                    </div>
                </div>
                <hr>
                <div v-for="i in feederData.currents" class="w3-row">
                    <div class="w3-col l4">{{ i.label }}</div>
                    <div class="w3-col l8">
                        <input type="text" class="readonly-textbox" :value="i.value" readonly>
                    </div>
                </div>
            </div>
            <div class="w3-col l6">
                <div v-for="p in feederData.powers" class="w3-row">
                    <div class="w3-col l4">{{ p.label }}</div>
                    <div class="w3-col l8">
                        <input type="text" class="readonly-textbox" :value="p.value" readonly>
                    </div>
                </div>
                <hr>
                <div v-for="m in feederData.misc" class="w3-row">
                    <div class="w3-col l4">{{ m.label }}</div>
                    <div class="w3-col l8">
                        <input type="text" class="readonly-textbox" :value="m.value" readonly>
                    </div>
                </div>
            </div>
        </div>
        <hr>
    </div>
    `
});
