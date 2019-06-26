<template>
    <div class="line-item">
        <div class="order-flex">
            <div class="line-item-title">
                <div class="light"><code>{{ lineItem.sku }}</code></div>

                <br />

                <!-- Shipping & Tax -->
                <small>
                    <ul>
                        <li>
                            {{shippingCategory}} <span class="light"><small>({{"Shipping"|t('commerce')}})</small></span>
                            <input-error :error-key="'order.lineItems.'+lineItemKey+'.shippingCategoryId'"></input-error>
                        </li>
                        <li>{{taxCategory}} <span class="light">({{"Tax"|t('commerce')}})</span></li>
                    </ul>
                </small>

                <!-- Snapshot -->
                <div>
                    <template v-if="!showSnapshot">
                        <a @click.prevent="showSnapshot = true">{{"Snapshot"|t('commerce')}} <i data-icon="downangle"></i></a>
                    </template>
                    <template v-else>
                        <a @click.prevent="showSnapshot = false">{{"Hide snapshot"|t('commerce')}} <i data-icon="upangle"></i></a>
                        <div>
                            <pre><code>{{lineItem.snapshot}}</code></pre>
                        </div>
                    </template>
                </div>
            </div>

            <div class="order-flex-grow">
                <div class="order-indented-block">
                    <div class="order-flex">
                        <div class="order-block-title">
                            <!-- Description -->
                            <h3>{{ lineItem.description }}</h3>

                            <!-- Status -->
                            <line-item-status :line-item="lineItem" :editing="editing" @change="updateLineItemStatusId"></line-item-status>
                        </div>
                        <div class="order-flex-grow">
                            <ul>
                                <li class="order-flex">
                                    <template v-if="editing && recalculationMode === 'none'">
                                        <field :label="$options.filters.t('Sale Price', 'commerce')" :errors="getErrors('order.lineItems.'+lineItemKey+'.salePrice')">
                                            <input type="text" class="text" size="10" v-model="salePrice" />
                                        </field>
                                    </template>
                                    <template v-else>
                                        <label class="light" for="salePrice">{{"Sale Price"|t('commerce')}}</label>
                                        <div>{{ lineItem.salePriceAsCurrency }}</div>
                                    </template>
                                </li>
                                <template v-if="lineItem.onSale">
                                    <li><span class="light">{{"Original Price"|t('commerce')}}</span>&nbsp;<strike>{{ lineItem.priceAsCurrency }}</strike></li>
                                    <li><span class="light">{{"Sale Amount Off"|t('commerce')}}</span> {{ lineItem.saleAmountAsCurrency }}</li>
                                </template>
                            </ul>

                        </div>
                        <div class="order-flex-grow">
                            <div class="order-flex">
                                <template v-if="!editing">
                                    <label class="light" for="quantity">{{"Quantity"|t('commerce')}}</label>
                                    <span>{{ lineItem.qty }}</span>
                                </template>
                                <template v-else>
                                    <field :label="$options.filters.t('Quantity', 'commerce')" :errors="getErrors('order.lineItems.'+lineItemKey+'.qty')">
                                        <input type="text" class="text" size="3" v-model="qty" />
                                    </field>
                                </template>
                            </div>
                        </div>
                        <div class="order-flex-grow text-right">
                            {{lineItem.subtotalAsCurrency}}
                        </div>
                    </div>
                </div>

                <line-item-options :line-item="lineItem" :editing="editing" @updateLineItem="$emit('updateLineItem', $event)"></line-item-options>
                <line-item-notes :line-item="lineItem" :editing="editing" @updateLineItem="$emit('updateLineItem', $event)"></line-item-notes>
                <line-item-adjustments :order-id="orderId" :line-item="lineItem" :editing="editing" :recalculation-mode="recalculationMode" :errorPrefix="'order.lineItems.'+lineItemKey+'.adjustments.'" @updateLineItem="$emit('updateLineItem', $event)"></line-item-adjustments>
                <div class="order-indented-block text-right">
                    <div>
                        <strong>{{ lineItem.totalAsCurrency }}</strong>
                    </div>
                    <div v-if="editing">
                        <a @click.prevent="$emit('removeLineItem')">{{"Remove"|t('commerce')}}</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
    import {mapGetters} from 'vuex'
    import debounce from 'lodash.debounce'
    import InputError from '../InputError'
    import Field from '../Field'
    import LineItemStatus from './LineItemStatus'
    import LineItemOptions from './LineItemOptions'
    import LineItemNotes from './LineItemNotes'
    import LineItemAdjustments from './LineItemAdjustments'

    export default {
        components: {
            InputError,
            Field,
            LineItemStatus,
            LineItemOptions,
            LineItemNotes,
            LineItemAdjustments,
        },

        props: {
            lineItem: {
                type: Object,
            },
            lineItemKey: {
                type: Number,
            },
            recalculationMode: {
                type: String,
            },
            editing: {
                type: Boolean,
            },
        },

        data() {
            return {
                showSnapshot: false,
            }
        },

        computed: {
            ...mapGetters([
                'getErrors',
                'shippingCategories',
                'taxCategories',
                'orderId',
            ]),

            salePrice: {
                get() {
                    return this.lineItem.salePrice
                },
                set: debounce(function(val) {
                    const lineItem = this.lineItem
                    lineItem.salePrice = val
                    this.$emit('updateLineItem', lineItem)
                }, 1000)
            },

            qty: {
                get() {
                    return this.lineItem.qty
                },
                set: debounce(function(val) {
                    const lineItem = this.lineItem
                    lineItem.qty = val
                    this.$emit('updateLineItem', lineItem)
                }, 1000)
            },

            shippingCategory() {
                if (!this.lineItem.shippingCategoryId) {
                    return null
                }

                if (typeof this.shippingCategories[this.lineItem.shippingCategoryId] === 'undefined') {
                    return this.lineItem.shippingCategoryId
                }

                return this.shippingCategories[this.lineItem.shippingCategoryId]
            },

            taxCategory() {
                if (!this.lineItem.taxCategoryId) {
                    return null
                }

                if (typeof this.taxCategories[this.lineItem.taxCategoryId] === 'undefined') {
                    return this.lineItem.taxCategoryId
                }

                return this.taxCategories[this.lineItem.taxCategoryId]
            },
        },

        methods: {
            updateLineItemStatusId(lineItemStatusId) {
                const lineItem = this.lineItem
                lineItem.lineItemStatusId = lineItemStatusId
                this.$emit('updateLineItem', lineItem)
            },
        },
    }
</script>