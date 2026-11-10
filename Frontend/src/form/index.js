/* 模板 */
const base = [
  {
    attr: {
      label: '上传文件',
      prop: 'upload'
    },
    value: '',
    name: 'Upload',
    item: {
      attr: {
        accept: 'image/*'
      }
    }
  },
  {
    attr: {
      label: '多个文件',
      prop: 'upload'
    },
    value: '',
    name: 'UploadMultiple',
    item: {
      attr: {
        accept: 'image/*'
      }
    }
  },
  {
    attr: {
      label: '富文本',
      prop: 'tinymce'
    },
    value: '',
    name: 'Tinymce'
  },
  {
    attr: {
      label: '普通文本',
      prop: 'text'
    },
    value: '',
    name: 'BaseInput',
    item: {
      attr: {
        placeholder: '请输入'
      }
    }
  },
  {
    attr: {
      label: '下拉文本',
      prop: 'selectText'
    },
    value: '',
    name: 'BaseInput',
    item: {
      attr: {
        clearable: true,
        placeholder: '请输入'
      },
      select: [
        { label: '选项1', value: '选项1' },
        { label: '选项2', value: '选项2' },
        { label: '选项3', value: '选项3' }
      ]
    }
  },
  {
    attr: {
      label: '多行文本',
      prop: 'textarea'
    },
    value: '',
    name: 'BaseInput',
    item: {
      attr: {
        type: 'textarea',
        placeholder: '请输入',
        rows: 5
      }
    }
  },
  {
    attr: {
      label: '滑块',
      prop: 'slider'
    },
    value: 0,
    name: 'BaseSlider',
    item: {
      attr: {
        min: 0,
        max: 100
      }
    }
  },
  {
    attr: {
      prop: 'date',
      label: '日期选择'
    },
    value: [],
    name: 'BaseDatePicker',
    item: {
      attr: {
        type: 'daterange',
        'value-format': 'yyyy-MM-dd',
        'start-placeholder': '开始日期',
        'end-placeholder': '结束日期',
      },
      pickerOptions: ['最近一周', '最近一个月', '最近三个月']
    }
  },
  {
    attr: {
      prop: 'time',
      label: '时间选择'
    },
    value: [],
    name: 'BaseTimePicker',
    item: {
      attr: {
        'is-range': true,
        'picker-options': { format: 'HH:mm:ss' },
        'value-format': 'HH:mm:ss',
        'start-placeholder': '开始时间',
        'end-placeholder': '结束时间',
      }
    }
  },
  {
    attr: {
      label: '下拉选择',
      prop: 'select'
    },
    value: [],
    name: 'BaseSelect',
    item: {
      attr: {
        placeholder: '请选择',
        clearable: true,
        multiple: true,
        filterable: true,
        'allow-create': true,
        'default-first-option': true
      },
      options: [
        { label: '选项1', value: '选项1' },
        { label: '选项2', value: '选项2' },
        { label: '选项3', value: '选项3' }
      ]
    }
  },
  {
    attr: {
      label: '级联选择',
      prop: 'cascader'
    },
    value: [],
    name: 'BaseCascader',
    item: {
      attr: {
        placeholder: '请选择',
        options: [
          {
            value: '1',
            label: '选项1',
            children: [
              {
                value: '1-1',
                label: '选项1-1',
                children: [
                  {
                    value: '1-1-1',
                    label: '选项1-1-1'
                  }
                ]
              },
              {
                value: '1-2',
                label: '选项1-2',
              }
            ]
          },
          {
            value: '2',
            label: '选项2',
            children: []
          }
        ]
      },
      props: {}
    }
  },
  {
    attr: {
      label: '多选框',
      prop: 'checkbox'
    },
    value: [],
    name: 'BaseCheckbox',
    item: {
      attr: {
        max: 2
      },
      options: [
        { label: '选项1', value: '选项1' },
        { label: '选项2', value: '选项2' },
        { label: '选项3', value: '选项3' }
      ]
    }
  },
  {
    attr: {
      label: '单选框',
      prop: 'radio'
    },
    value: '',
    name: 'BaseRadio',
    item: {
      options: [
        { label: '选项1', value: '选项1' },
        { label: '选项2', value: '选项2' },
        { label: '选项3', value: '选项3' }
      ]
    }
  },
  {
    attr: {
      label: '评分',
      prop: 'rate'
    },
    value: 0,
    name: 'BaseRate',
    item: {
      attr: {
        max: 6
      }
    }
  },
  {
    attr: {
      label: '数字范围',
      prop: 'inputRange',
    },
    value: ['', ''],
    name: 'BaseInputRange'
  },
  {
    attr: {
      label: '计数器',
      prop: 'inputNum',
    },
    value: '0',
    name: 'BaseInputNumber',
    unit: '单位',
    desc: {
      content: '提示'
    },
    item: {
      attr: {
        min: 0,
        max: 100
      }
    }
  },
  {
    attr: {
      label: '开关',
      prop: 'switch'
    },
    value: true,
    name: 'BaseSwitch'
  },
  {
    attr: {
      label: '颜色选择',
      prop: 'color'
    },
    value: '',
    name: 'BaseColorPicker'
  }
]

export const admin = {
  SFC: {
    attr: {
      'label-position': 'top'
    },
    formItems: [
      {
        attr: {
          label: 'admin.name',
          prop: 'username'
        },
        value: '',
        name: 'BaseInput',
        item: {
          attr: {
            placeholder: 'admin.namePh'
          }
        }
      },
      {
        attr: {
          label: 'role.name',
          prop: 'role_id'
        },
        value: '',
        name: 'BaseSelect',
        item: {
          attr: {
            placeholder: 'common.selectPh',
            clearable: true
          },
          options: []
        }
      },
      {
        attr: {
          label: 'common.status',
          prop: 'status'
        },
        value: '',
        name: 'BaseSelect',
        item: {
          attr: {
            placeholder: 'common.selectPh',
            clearable: true
          },
          options: [
            { label: 'common.normal', value: 1 },
            { label: 'common.disabled', value: 0 }
          ]
        }
      }
    ]
  },
  EFC: {
    attr: {
      'label-position': 'top'
    },
    formItems: [
      {
        attr: {
          label: 'common.avatar',
          prop: 'avatar'
        },
        value: '',
        name: 'upload',
        item: {
          attr: {
            accept: 'image/*'
          }
        }
      },
      {
        attr: {
          label: 'admin.name',
          prop: 'username'
        },
        value: '',
        name: 'BaseInput',
        item: {
          attr: {
            placeholder: 'admin.namePh'
          }
        }
      },
      {
        attr: {
          label: 'login.password',
          prop: 'password'
        },
        value: '',
        name: 'BaseInput',
        item: {
          attr: {
            type: 'password',
            placeholder: 'login.psdPh',
            disabled: false,
            'show-password': true
          }
        }
      },
      {
        attr: {
          label: 'role.name',
          prop: 'role_id'
        },
        value: '',
        name: 'BaseSelect',
        item: {
          attr: {
            placeholder: 'common.selectPh',
            clearable: true
          },
          options: []
        }
      },
      {
        attr: {
          label: 'common.status',
          prop: 'status'
        },
        value: 1,
        name: 'BaseSelect',
        item: {
          attr: {
            placeholder: 'common.selectPh',
            clearable: true,
            disabled: true
          },
          options: [
            { label: 'common.normal', value: 1 },
            { label: 'common.disabled', value: 0 }
          ]
        }
      },
      {
        attr: {
          label: 'common.tel',
          prop: 'phone'
        },
        value: '',
        name: 'BaseInput',
        item: {
          attr: {
            placeholder: 'common.telPh'
          }
        }
      },
      {
        attr: {
          label: 'common.email',
          prop: 'email'
        },
        value: '',
        name: 'BaseInput',
        item: {
          attr: {
            placeholder: 'common.emailPh'
          }
        }
      },
    ]
  }
}

export const goodsCate = {
  SFC: {
    attr: {
      'label-position': 'top'
    },
    formItems: [
      {
        attr: {
          label: 'goodsCate.name',
          prop: 'product_sort_name'
        },
        value: '',
        name: 'BaseInput',
        item: {
          attr: {
            placeholder: 'goodsCate.namePh'
          }
        }
      },
      {
        attr: {
          label: 'common.status',
          prop: 'status'
        },
        value: '',
        name: 'BaseSelect',
        item: {
          attr: {
            placeholder: 'common.selectPh',
            clearable: true
          },
          options: [
            { label: 'common.normal', value: 1 },
            { label: 'common.disabled', value: 0 }
          ]
        }
      }
    ]
  },
  EFC: {
    attr: {
      'label-position': 'top'
    },
    formItems: [
      {
        attr: {
          label: 'common.image',
          prop: 'product_sort_img'
        },
        value: '',
        name: 'baseFile',
        item: {
          attr: {
            limit: 1
          }
        }
      },
      {
        attr: {
          label: 'goodsCate.zh_name',
          prop: 'product_sort_name'
        },
        value: '',
        name: 'BaseInput',
        item: {
          attr: {
            placeholder: 'goodsCate.namePh'
          }
        }
      },
      {
        attr: {
          label: 'classificationList.brand',
          prop: 'card_brands'
        },
        value: [],
        name: 'BaseSelect',
        item: {
          attr: {
            placeholder: 'classificationList.brandPh',
            multiple: true,
            filterable: true,
            allowCreate: true,
            defaultFirstOption: true,
            clearable: true,
            'collapse-tags': true
          }
        }
      },
      {
        attr: {
          label: 'common.status',
          prop: 'status'
        },
        value: 1,
        name: 'BaseSelect',
        item: {
          attr: {
            placeholder: 'common.selectPh',
            clearable: true
          },
          options: [
            { label: 'common.normal', value: 1 },
            { label: 'common.disabled', value: 0 }
          ]
        }
      }
    ]
  }
}

export const goods = {
  SFC: {
    attr: {
      'label-position': 'top'
    },
    formItems: [
      {
        attr: {
          label: 'goods.id',
          prop: 'product_id'
        },
        value: '',
        name: 'BaseInput',
        item: {
          attr: {
            placeholder: 'common.enter'
          }
        }
      },
      {
        attr: {
          label: 'goods.name',
          prop: 'product_name'
        },
        value: '',
        name: 'BaseInput',
        item: {
          attr: {
            placeholder: 'common.enter'
          }
        }
      },
      {
        attr: {
          label: 'goods.barcode',
          prop: 'product_barcode'
        },
        value: '',
        name: 'BaseInput',
        item: {
          attr: {
            placeholder: 'common.enter'
          }
        }
      },
      {
        attr: {
          label: 'goods.cate',
          prop: 'product_sort_id'
        },
        value: '',
        name: 'BaseSelect',
        item: {
          attr: {
            placeholder: 'common.selectPh',
            clearable: true
          },
          options: []
        }
      }
    ]
  },
  EFC: {
    flex: true,
    attr: {
      'label-position': 'top'
    },
    formItems: [
      [
        {
          attr: {
            label: 'goods.image',
            prop: 'product_img'
          },
          value: '',
          name: 'baseFile',
          item: {
            attr: {
              limit: 1
            }
          }
        }
      ],
      [
        {
          attr: {
            label: 'goods.zh_name',
            prop: 'product_name'
          },
          value: '',
          name: 'BaseInput',
          item: {
            attr: {
              placeholder: 'common.enter'
            }
          }
        },
        {
          attr: {
            label: 'goods.en_name',
            prop: 'en_product_name'
          },
          value: '',
          name: 'BaseInput',
          item: {
            attr: {
              placeholder: 'common.enter'
            }
          }
        }
      ],
      [
        {
          attr: {
            label: 'goods.zh_syno',
            prop: 'product_synopsis'
          },
          value: '',
          name: 'BaseInput',
          item: {
            attr: {
              placeholder: 'common.enter'
            }
          }
        },
        {
          attr: {
            label: 'goods.en_syno',
            prop: 'en_product_synopsis'
          },
          value: '',
          name: 'BaseInput',
          item: {
            attr: {
              placeholder: 'common.enter'
            }
          }
        }
      ],
      [
        {
          attr: {
            label: 'goods.pay_type',
            prop: 'product_pay_type',
          },
          value: 3,
          name: 'BaseRadio',
          item: {
            attr: {
              options: [
                { label: 'goods.coin', value: 1 },
                { label: 'goods.diamond', value: 2 },
                { label: 'goods.all', value: 3 }
              ]
            }
          }
        },
        {
          attr: {
            label: 'goods.type',
            prop: 'product_type'
          },
          value: '',
          name: 'BaseSelect',
          item: {
            attr: {
              placeholder: 'common.selectPh',
              clearable: true
            },
            options: [
              { label: 'goods.product', value: 1 },
              { label: 'goods.services', value: 2 }
            ]
          }
        },
        {
          attr: {
            label: 'goods.cate',
            prop: 'product_sort_id'
          },
          value: '',
          name: 'BaseSelect',
          item: {
            attr: {
              placeholder: 'common.selectPh',
              clearable: true
            },
            options: []
          }
        }
      ],
      [
        {
          attr: {
            label: 'goods.barcode',
            prop: 'product_barcode'
          },
          value: '',
          name: 'BaseInput',
          item: {
            attr: {
              placeholder: 'common.enter'
            }
          }
        },
        {
          attr: {
            label: 'goods.location',
            prop: 'product_location'
          },
          value: '',
          name: 'BaseInput',
          item: {
            attr: {
              placeholder: 'common.enter'
            }
          }
        }
      ],
      [
        {
          attr: {
            label: 'goods.price',
            prop: 'product_price',
          },
          value: 0,
          name: 'BaseInputNumber',
          item: {
            attr: {
              min: 0
            }
          }
        },
        {
          attr: {
            label: 'goods.cost',
            prop: 'product_cost',
          },
          value: 0,
          name: 'BaseInputNumber',
          item: {
            attr: {
              min: 0
            }
          }
        }
      ],
      [
        {
          attr: {
            label: 'goods.stock',
            prop: 'product_stock',
          },
          value: 0,
          name: 'BaseInputNumber',
          desc: 'goods.stock_desc',
          item: {
            attr: {
              min: 0
            }
          }
        },
        {
          attr: {
            label: 'goods.hand',
            prop: 'product_hand',
          },
          value: 0,
          name: 'BaseInputNumber',
          // unit: '单位',
          // desc: {
          //   content: '提示'
          // },
          item: {
            attr: {
              min: 0
            }
          }
        }
      ]
    ]
  }
}

export const coupon = {
  SFC: {
    attr: {
      'label-position': 'top'
    },
    formItems: [
      {
        attr: {
          label: 'coupon.name',
          prop: 'coupon_name'
        },
        value: '',
        name: 'BaseInput',
        item: {
          attr: {
            placeholder: 'common.enter'
          }
        }
      },
      {
        attr: {
          label: 'coupon.type',
          prop: 'coupon_type'
        },
        value: '',
        name: 'BaseSelect',
        item: {
          attr: {
            placeholder: 'common.selectPh',
            clearable: true
          },
          options: [
            { label: 'coupon.type1', value: 1 },
            { label: 'coupon.type2', value: 2 },
            { label: 'coupon.type3', value: 3 },
            { label: 'coupon.type4', value: 4 }
          ]
        }
      }
    ]
  },
  EFC: {
    flex: true,
    attr: {
      'label-position': 'top'
    },
    formItems: [
      [
        {
          attr: {
            label: 'coupon.zh_name',
            prop: 'coupon_name'
          },
          value: '',
          name: 'BaseInput',
          item: {
            attr: {
              placeholder: 'common.enter'
            }
          }
        },
        {
          attr: {
            label: 'coupon.en_name',
            prop: 'en_coupon_name'
          },
          value: '',
          name: 'BaseInput',
          item: {
            attr: {
              placeholder: 'common.enter'
            }
          }
        }
      ],
      [
        {
          attr: {
            label: 'coupon.zh_inst',
            prop: 'instructions'
          },
          value: '',
          name: 'BaseInput',
          item: {
            attr: {
              type: 'textarea',
              placeholder: 'common.enter'
            }
          }
        },
        {
          attr: {
            label: 'coupon.en_inst',
            prop: 'en_instructions'
          },
          value: '',
          name: 'BaseInput',
          item: {
            attr: {
              type: 'textarea',
              placeholder: 'common.enter'
            }
          }
        }
      ],
      [
        {
          attr: {
            label: 'coupon.type',
            prop: 'coupon_type'
          },
          value: '',
          name: 'BaseSelect',
          item: {
            attr: {
              placeholder: 'common.selectPh',
              clearable: true
            },
            options: [
              { label: 'coupon.type1', value: 1 },
              { label: 'coupon.type2', value: 2 },
              { label: 'coupon.type3', value: 3 },
              { label: 'coupon.type4', value: 4 }
            ]
          }
        },
        {
          attr: {
            label: 'common.status',
            prop: 'coupon_status'
          },
          value: 1,
          name: 'BaseSelect',
          item: {
            attr: {
              placeholder: 'common.selectPh',
              clearable: true
            },
            options: [
              { label: 'common.normal', value: 1 },
              { label: 'common.disabled', value: 0 }
            ]
          }
        }
      ],
      [
        {
          attr: {
            label: 'coupon.member_level',
            prop: 'lowlevel'
          },
          value: '',
          name: 'BaseSelect',
          item: {
            attr: {
              placeholder: 'common.selectPh',
              clearable: true
            },
            options: []
          }
        }
      ],
      [
        {
          attr: {
            label: 'coupon.conversion_price',
            prop: 'conversion_price',
          },
          value: '0',
          name: 'BaseInputNumber',
          item: {
            attr: {
              min: 0
            }
          }
        },
        {
          attr: {
            label: 'goods.stock',
            prop: 'coupon_stock',
          },
          value: '',
          name: 'BaseInputNumber',
          item: {
            attr: {
              min: 0
            }
          }
        }
      ],
      [
        {
          attr: {
            label: 'coupon.discount',
            prop: 'discount',
          },
          value: '0',
          name: 'BaseInputNumber',
          desc: 'coupon.disc_tips',
          item: {
            attr: {
              min: 0
            }
          }
        },
        {
          attr: {
            label: 'coupon.lowest',
            prop: 'lowest',
          },
          value: '0',
          name: 'BaseInputNumber',
          desc: 'coupon.lowe_tips',
          item: {
            attr: {
              min: 0
            }
          }
        }
      ],
      [
        {
          attr: {
            prop: 'expiration_time',
            label: 'coupon.expiration_time'
          },
          value: '',
          name: 'BaseDatePicker',
          item: {
            attr: {
              type: 'datetime',
              'value-format': 'yyyy-MM-dd HH:mm:ss',
              placeholder: 'common.selectPh'
              // 'start-placeholder': '开始日期',
              // 'end-placeholder': '结束日期',
            }
          }
        },
      ]
    ]
  }
}

export const loss = {
  SFC: {
    attr: {
      'label-position': 'top'
    },
    formItems: [
      {
        attr: {
          label: 'goods.name',
          prop: 'product_name'
        },
        value: '',
        name: 'BaseInput',
        item: {
          attr: {
            placeholder: 'common.enter'
          }
        }
      },
      {
        attr: {
          prop: 'time',
          label: 'common.date'
        },
        value: [],
        name: 'BaseDatePicker',
        item: {
          attr: {
            type: 'datetimerange',
            'value-format': 'yyyy-MM-dd HH:mm:ss'
          },
          // pickerOptions: ['最近一周', '最近一个月', '最近三个月']
        }
      }
    ]
  },
  EFC: {
    attr: {
      'label-position': 'top'
    },
    formItems: [
      {
        attr: {
          label: 'goods.specs',
          prop: 'product'
        },
        value: [],
        name: 'BaseCascader',
        item: {
          attr: {
            placeholder: 'common.selectPh',
            disabled: false,
            options: []
          },
          props: { checkStrictly: true }
        }
      },
      {
        attr: {
          label: 'loss.number',
          prop: 'wastage_number',
        },
        value: '1',
        name: 'BaseInputNumber',
        item: {
          attr: {
            min: 1
          }
        }
      },
      {
        attr: {
          label: 'loss.remark',
          prop: 'wastage_remarks'
        },
        value: '',
        name: 'BaseInput',
        item: {
          attr: {
            placeholder: 'common.enter'
          }
        }
      }
    ]
  }
}

export const purchase = {
  SFC: {
    attr: {
      'label-position': 'top'
    },
    formItems: [
      {
        attr: {
          label: 'goods.name',
          prop: 'product_name'
        },
        value: '',
        name: 'BaseInput',
        item: {
          attr: {
            placeholder: 'common.enter'
          }
        }
      },
      {
        attr: {
          prop: 'time',
          label: 'common.date'
        },
        value: [],
        name: 'BaseDatePicker',
        item: {
          attr: {
            type: 'datetimerange',
            'value-format': 'yyyy-MM-dd HH:mm:ss'
          },
          // pickerOptions: ['最近一周', '最近一个月', '最近三个月']
        }
      }
    ]
  },
  EFC: {
    attr: {
      'label-position': 'top'
    },
    formItems: [
      {
        attr: {
          label: 'goods.specs',
          prop: 'product'
        },
        value: [],
        name: 'BaseCascader',
        item: {
          attr: {
            placeholder: 'common.selectPh',
            options: []
          },
          props: { checkStrictly: true },
          listeners: {}
        }
      },
      {
        attr: {
          label: 'purchase.number',
          prop: 'purchase_number',
        },
        value: '0',
        name: 'BaseInputNumber',
        item: {
          attr: {
            min: 0
          }
        }
      },
      {
        attr: {
          label: 'purchase.cost',
          prop: 'purchase_cost',
        },
        value: '0',
        name: 'BaseInputNumber',
        item: {
          attr: {
            min: 0
          }
        }
      },
      {
        attr: {
          label: 'purchase.remark',
          prop: 'purchase_remarks'
        },
        value: '',
        name: 'BaseInput',
        item: {
          attr: {
            placeholder: 'common.enter'
          }
        }
      },
      {
        attr: {
          label: 'purchase.supplier',
          prop: 'purchase_supplier'
        },
        value: '',
        name: 'BaseInput',
        item: {
          attr: {
            placeholder: 'common.enter'
          }
        }
      },
      {
        attr: {
          label: 'purchase.contact',
          prop: 'purchase_contact'
        },
        value: '',
        name: 'BaseInput',
        item: {
          attr: {
            placeholder: 'common.enter'
          }
        }
      }
    ]
  }
}

export const banner = {
  SFC: {
    attr: {
      'label-position': 'top'
    },
    formItems: [
      {
        attr: {
          label: 'banner.title',
          prop: 'banner_title'
        },
        value: '',
        name: 'BaseInput',
        item: {
          attr: {
            placeholder: 'common.enter'
          }
        }
      },
      {
        attr: {
          label: 'banner.url',
          prop: 'banner_url'
        },
        value: '',
        name: 'BaseInput',
        item: {
          attr: {
            placeholder: 'common.enter'
          }
        }
      },
      {
        attr: {
          label: 'common.status',
          prop: 'banner_status'
        },
        value: '',
        name: 'BaseSelect',
        item: {
          attr: {
            placeholder: 'common.selectPh',
            clearable: true
          },
          options: [
            { label: 'common.normal', value: 1 },
            { label: 'common.disabled', value: 0 }
          ]
        }
      }
    ]
  },
  EFC: {
    attr: {
      'label-position': 'top'
    },
    formItems: [
      {
        attr: {
          label: 'goods.image',
          prop: 'banner_img'
        },
        value: '',
        name: 'baseFile',
        tip: '建议尺寸1600*900px',
        item: {
          attr: {
            limit: 1
          }
        }
      },
      {
        attr: {
          label: 'banner.zh_title',
          prop: 'banner_title'
        },
        value: '',
        name: 'BaseInput',
        item: {
          attr: {
            placeholder: 'common.enter'
          }
        }
      },
      {
        attr: {
          label: 'banner.url',
          prop: 'banner_url'
        },
        value: '',
        name: 'BaseInput',
        item: {
          attr: {
            placeholder: 'common.enter'
          }
        }
      },
      {
        attr: {
          label: 'common.status',
          prop: 'banner_status'
        },
        value: 1,
        name: 'BaseSelect',
        item: {
          attr: {
            placeholder: 'common.selectPh',
            disabled: false
          },
          options: [
            { label: 'common.normal', value: 1 },
            { label: 'common.disabled', value: 0 }
          ]
        }
      }
    ]
  }
}

export const bannerPopup = {
  SFC: {
    attr: {
      'label-position': 'top'
    },
    formItems: []
  },
  EFC: {
    attr: {
      'label-position': 'top'
    },
    formItems: [
      {
        attr: {
          label: 'bannerPopup.img',
          prop: 'popup_img'
        },
        value: '',
        name: 'baseFile',
        item: {
          attr: {
            limit: 1
          }
        }
      },
      {
        attr: {
          label: 'bannerPopup.title',
          prop: 'popup_title'
        },
        value: '',
        name: 'BaseInput',
        item: {
          attr: {
            placeholder: 'common.enter'
          }
        }
      },
      {
        attr: {
          label: 'bannerPopup.enable',
          prop: 'popup_enable'
        },
        value: 1,
        name: 'BaseSwitch',
        item: {
          attr: {
            activeValue: 1,
            inactiveValue: 0
          }
        }
      },
      {
        attr: {
          label: 'common.status',
          prop: 'popup_status'
        },
        value: 1,
        name: 'BaseSelect',
        item: {
          attr: {
            placeholder: 'common.selectPh'
          },
          options: [
            { label: 'common.normal', value: 1 },
            { label: 'common.disabled', value: 0 }
          ]
        }
      }
    ]
  }
}

export const faq = {
  SFC: {
    attr: {
      'label-position': 'top'
    },
    formItems: [
      {
        attr: {
          label: 'faq.qus',
          prop: 'faq_title'
        },
        value: '',
        name: 'BaseInput',
        item: {
          attr: {
            placeholder: 'common.enter'
          }
        }
      },
      {
        attr: {
          label: 'common.status',
          prop: 'faq_status'
        },
        value: '',
        name: 'BaseSelect',
        item: {
          attr: {
            placeholder: 'common.selectPh',
            clearable: true
          },
          options: [
            { label: 'common.normal', value: 1 },
            { label: 'common.disabled', value: 0 }
          ]
        }
      }
    ]
  },
  EFC: {
    attr: {
      'label-position': 'top'
    },
    formItems: [
      {
        attr: {
          label: 'faq.zh_qus',
          prop: 'faq_title'
        },
        value: '',
        name: 'BaseInput',
        item: {
          attr: {
            placeholder: 'common.enter'
          }
        }
      },
      {
        attr: {
          label: 'faq.zh_anw',
          prop: 'faq_result'
        },
        value: '',
        name: 'QuillEditor'
      },
      {
        attr: {
          label: 'common.status',
          prop: 'faq_status'
        },
        value: 1,
        name: 'BaseSelect',
        item: {
          attr: {
            placeholder: 'common.selectPh',
            disabled: false
          },
          options: [
            { label: 'common.normal', value: 1 },
            { label: 'common.disabled', value: 0 }
          ]
        }
      }
    ]
  }
}

export const user = {
  SFC: {
    attr: {
      'label-position': 'top'
    },
    formItems: [
      {
        attr: {
          label: 'common.keywords',
          prop: 'multi'
        },
        value: '',
        name: 'BaseInput',
        item: {
          attr: {
            placeholder: 'user.multi'
          }
        }
      },
      {
        attr: {
          label: 'common.status',
          prop: 'user_status'
        },
        value: '',
        name: 'BaseSelect',
        item: {
          attr: {
            placeholder: 'common.selectPh',
            // clearable: true
          },
          options: [
            { label: 'common.normal', value: 1 },
            { label: 'common.disabled', value: 0 }
          ]
        }
      }
    ]
  },
  EFC: {
    attr: {
      'label-position': 'top'
    },
    formItems: [
      {
        attr: {
          label: 'user.name',
          prop: 'user_name'
        },
        value: '',
        name: 'BaseInput',
        item: {
          attr: {
            placeholder: 'common.enter'
          }
        }
      },
      {
        attr: {
          label: 'login.password',
          prop: 'user_password'
        },
        value: '',
        name: 'BaseInput',
        item: {
          attr: {
            type: 'password',
            placeholder: 'common.enter',
            disabled: false,
            'show-password': true
          }
        }
      },
      {
        attr: {
          label: 'common.tel',
          prop: 'phone'
        },
        value: '852,',
        name: 'BaseInput',
        item: {
          attr: {
            clearable: true,
            placeholder: 'common.telPh'
          },
          select: [
            { label: 'user.cn', value: '86' },
            { label: 'user.hk', value: '852' }
          ]
        }
      },
      {
        attr: {
          label: 'common.email',
          prop: 'user_email'
        },
        value: '',
        name: 'BaseInput',
        item: {
          attr: {
            placeholder: 'common.emailPh'
          }
        }
      },
      {
        attr: {
          label: 'user.sex',
          prop: 'user_sex'
        },
        value: 1,
        name: 'BaseSelect',
        item: {
          attr: {
            disabled: false,
            clearable: true,
            placeholder: 'user.sex'
          },
          options: [
            { label: 'user.sex1', value: 1 },
            { label: 'user.sex0', value: 0 }
          ]
        }
      },
      {
        attr: {
          label: 'common.status',
          prop: 'user_status'
        },
        value: 1,
        name: 'BaseSelect',
        item: {
          attr: {
            placeholder: 'common.selectPh',
            disabled: false
          },
          options: [
            { label: 'common.normal', value: 1 },
            { label: 'common.disabled', value: 0 }
          ]
        }
      },
      {
        attr: {
          label: 'user.coin',
          prop: 'user_coin'
        },
        value: 0,
        name: 'BaseInputNumber',
        item: {
          attr: {
            min: 0
          }
        }
      },
      {
        attr: {
          label: 'user.integral',
          prop: 'user_integral'
        },
        value: 0,
        name: 'BaseInputNumber',
        item: {
          attr: {
            min: 0
          }
        }
      },
      {
        attr: {
          label: 'user.balance',
          prop: 'user_balance'
        },
        value: 0,
        name: 'BaseInputNumber',
        item: {
          attr: {
            min: 0
          }
        }
      },
      {
        attr: {
          label: 'user.login_time',
          prop: 'login_time'
        },
        value: 0,
        name: 'BaseInputNumber',
        item: {
          attr: {
            min: 0
          }
        }
      },
    ]
  }
}

export const store = {
  flex: true,
  attr: {
    'label-position': 'top'
  },
  formItems: [
    [
      {
        attr: {
          label: 'common.logo',
          prop: 'shop_logo'
        },
        value: '',
        name: 'Upload',
        item: {
          attr: {
            accept: 'image/*'
          }
        }
      },
      {
        attr: {
          label: 'APP端後台圖片',
          prop: 'app_background_img'
        },
        value: '',
        name: 'Upload',
        tip: '建議尺寸：1375 * 768，更換 APP 端後台圖片',
        item: {
          attr: {
            accept: 'image/*'
          }
        }
      }
    ],
    [
      {
        attr: {
          label: 'store.name',
          prop: 'shop_name'
        },
        value: '',
        name: 'BaseInput',
        desc: 'store.to_long',
        item: {
          attr: {
            placeholder: 'common.enter'
          }
        }
      },
      {
        attr: {
          label: 'store.address',
          prop: 'shop_address'
        },
        value: '',
        name: 'BaseInput',
        item: {
          attr: {
            placeholder: 'common.enter'
          }
        }
      },
      {
        attr: {
          label: 'store.enaddress',
          prop: 'en_shop_address'
        },
        value: '',
        name: 'BaseInput',
        item: {
          attr: {
            placeholder: 'common.enter'
          }
        }
      }
    ],
    [
      {
        attr: {
          label: 'common.tel',
          prop: 'shop_phone'
        },
        value: '',
        name: 'BaseInput',
        item: {
          attr: {
            placeholder: 'common.enter'
          }
        }
      },
      {
        attr: {
          label: 'common.email',
          prop: 'shop_mail'
        },
        value: '',
        name: 'BaseInput',
        item: {
          attr: {
            placeholder: 'common.enter'
          }
        }
      }
    ],
    [
      {
        attr: {
          label: 'store.web',
          prop: 'shop_web'
        },
        value: '',
        name: 'BaseInput',
        item: {
          attr: {
            placeholder: 'common.enter'
          }
        }
      },

    ]
  ]
}

export const service = {
  attr: {
    'label-position': 'top'
  },
  formItems: [
    {
      attr: {
        label: 'service.url',
        prop: 'service_url'
      },
      value: '',
      name: 'BaseInput',
      item: {
        attr: {
          placeholder: 'common.enter'
        }
      }
    },
    {
      attr: {
        label: 'service.account',
        prop: 'service_account'
      },
      value: '',
      name: 'BaseInput',
      item: {
        attr: {
          placeholder: 'common.enter'
        }
      }
    },
    {
      attr: {
        label: 'common.status',
        prop: 'service_status'
      },
      value: 1,
      name: 'BaseSelect',
      item: {
        attr: {
          placeholder: 'common.selectPh'
        },
        options: [
          { label: 'common.normal', value: 1 },
          { label: 'common.disabled', value: 0 }
        ]
      }
    }
  ]
}

export const paypal = {
  attr: {
    'label-position': 'top'
  },
  formItems: [
    {
      attr: {
        label: 'paypal.client_id',
        prop: 'client_id'
      },
      value: '',
      name: 'BaseInput',
      item: {
        attr: {
          placeholder: 'common.enter'
        }
      }
    },
    {
      attr: {
        label: 'paypal.paypal_secret',
        prop: 'paypal_secret'
      },
      value: '',
      name: 'BaseInput',
      item: {
        attr: {
          placeholder: 'common.enter'
        }
      }
    },
    {
      attr: {
        label: 'paypal.paypal_enable',
        prop: 'paypal_enable'
      },
      value: 0,
      name: 'BaseSwitch',
      item: {
        attr: {
          'active-value': 1,
          'inactive-value': 0
        }
      }
    },
    {
      attr: {
        label: 'paypal.paypal_env',
        prop: 'paypal_env'
      },
      value: 1,
      name: 'BaseSelect',
      item: {
        attr: {
          placeholder: 'common.selectPh'
        },
        options: [
          { label: 'live', value: 1, trans: '1' },
          { label: 'sandbox', value: 2, trans: '1' }
        ]
      }
    },
    {
      attr: {
        label: 'common.status',
        prop: 'paypal_status'
      },
      value: 1,
      name: 'BaseSelect',
      item: {
        attr: {
          placeholder: 'common.selectPh'
        },
        options: [
          { label: 'common.normal', value: 1 },
          { label: 'common.disabled', value: 0 }
        ]
      }
    }
  ]
}

export const stripe = {
  attr: {
    'label-position': 'top'
  },
  formItems: [
    {
      attr: {
        label: 'stripe.stripe_key',
        prop: 'stripe_key'
      },
      value: '',
      name: 'BaseInput',
      item: {
        attr: {
          placeholder: 'common.enter'
        }
      }
    },
    {
      attr: {
        label: 'stripe.stripe_secret',
        prop: 'stripe_secret'
      },
      value: '',
      name: 'BaseInput',
      item: {
        attr: {
          placeholder: 'common.enter'
        }
      }
    },
    {
      attr: {
        label: 'stripe.stripe_enable',
        prop: 'stripe_enable'
      },
      value: 0,
      name: 'BaseSwitch',
      item: {
        attr: {
          'active-value': 1,
          'inactive-value': 0
        }
      }
    },
    {
      attr: {
        label: 'stripe.stripe_env',
        prop: 'stripe_env'
      },
      value: 1,
      name: 'BaseSelect',
      item: {
        attr: {
          placeholder: 'common.selectPh'
        },
        options: [
          { label: 'live', value: 1, trans: '1' },
          { label: 'sandbox', value: 2, trans: '1' }
        ]
      }
    },
    {
      attr: {
        label: 'common.status',
        prop: 'stripe_status'
      },
      value: 1,
      name: 'BaseSelect',
      item: {
        attr: {
          placeholder: 'common.selectPh'
        },
        options: [
          { label: 'common.normal', value: 1 },
          { label: 'common.disabled', value: 0 }
        ]
      }
    }
  ]
}
export const push = {
  attr: {
    'label-position': 'top'
  },
  formItems: [
    {
      attr: {
        label: 'pushMsg.push_title',
        prop: 'push_title'
      },
      value: '',
      name: 'BaseInput',
      item: {
        attr: {
          placeholder: 'common.enter'
        }
      }
    },
    {
      attr: {
        label: 'pushMsg.push_content',
        prop: 'push_content'
      },
      value: '',
      name: 'BaseInput',
      item: {
        attr: {
          type: 'textarea',
          placeholder: 'common.enter'
        }
      }
    },
    // {
    //   attr: {
    //     label: 'pushMsg.push_platform',
    //     prop: 'push_platform'
    //   },
    //   value: 0,
    //   name: 'BaseSelect',
    //   item: {
    //     attr: {
    //       placeholder: 'common.selectPh'
    //     },
    //     options: [0, 1, 2].map(value => ({ label: 'pushMsg.ppt' + value, value }))
    //   }
    // }
  ]
}

export const order = {
  SFC: {
    attr: {
      'label-position': 'top'
    },
    formItems: []
  }
}

export const offlineOrder = {
  attr: {
    'label-position': 'top'
  },
  formItems: [
    {
      attr: {
        label: 'goods.name',
        prop: 'product_name'
      },
      value: '',
      name: 'BaseInput',
      item: {
        attr: {
          placeholder: 'common.enter'
        }
      }
    },
    {
      attr: {
        label: 'goods.type',
        prop: 'product_type'
      },
      value: '',
      name: 'BaseSelect',
      item: {
        attr: {
          placeholder: 'common.selectPh',
          clearable: true
        },
        options: [
          { label: 'goods.product', value: 1 },
          { label: 'goods.services', value: 2 }
        ]
      }
    },
    {
      attr: {
        label: 'goods.cate',
        prop: 'product_sort_id'
      },
      value: '',
      name: 'BaseSelect',
      item: {
        attr: {
          placeholder: 'common.selectPh',
          clearable: true
        },
        options: []
      }
    }
  ]
}

export const appoNum = {
  flex: true,
  attr: {
    'label-position': 'top'
  },
  formItems: [
    [
      {
        attr: {
          label: 'appoNum.img',
          prop: 'reservation_project_url'
        },
        value: '',
        name: 'baseFile',
        item: {
          attr: {
            limit: 1
          }
        }
      }
    ],
    [
      {
        attr: {
          label: 'appoNum.zh_name',
          prop: 'reservation_project_name'
        },
        value: '',
        name: 'BaseInput',
        item: {
          attr: {
            placeholder: 'common.enter'
          }
        }
      },
      {
        attr: {
          label: 'appoNum.en_name',
          prop: 'en_reservation_project_name'
        },
        value: '',
        name: 'BaseInput',
        item: {
          attr: {
            placeholder: 'common.enter'
          }
        }
      },
    ],
    [
      {
        attr: {
          label: 'appoNum.zh_desc',
          prop: 'reservation_project_describe'
        },
        value: '',
        name: 'BaseInput',
        item: {
          attr: {
            type: 'textarea',
            placeholder: 'common.enter'
          }
        }
      },
      {
        attr: {
          label: 'appoNum.en_desc',
          prop: 'en_reservation_project_describe'
        },
        value: '',
        name: 'BaseInput',
        item: {
          attr: {
            type: 'textarea',
            placeholder: 'common.enter'
          }
        }
      },
    ],
    [
      {
        attr: {
          label: 'appoNum.stock',
          prop: 'reservation_project_stock',
        },
        value: 0,
        name: 'BaseInputNumber',
        item: {
          attr: {
            min: 0
          }
        }
      },
      {
        attr: {
          label: 'appoNum.time',
          prop: 'reservation_project_time',
        },
        value: 0,
        name: 'BaseInputNumber',
        item: {
          attr: {
            min: 0
          }
        }
      },
    ],
    [
      {
        attr: {
          label: 'appoNum.badge',
          prop: 'reservation_project_badge'
        },
        value: '',
        name: 'BaseInput',
        item: {
          attr: {
            placeholder: 'common.enter'
          }
        }
      },
      {
        attr: {
          label: 'common.status',
          prop: 'reservation_project_status'
        },
        value: 1,
        name: 'BaseSelect',
        item: {
          attr: {
            placeholder: 'common.selectPh',
            disabled: true
          },
          options: [
            { label: 'common.normal', value: 1 },
            { label: 'common.disabled', value: 0 }
          ]
        }
      }
    ]
  ]
}

export const userNum = {
  attr: {
    'label-position': 'top'
  },
  formItems: [
    {
      attr: {
        label: 'appoNum.name',
        prop: 'project_name'
      },
      value: '',
      name: 'BaseInput',
      item: {
        attr: {
          placeholder: 'common.enter'
        }
      }
    },
    {
      attr: {
        label: 'user.name',
        prop: 'user_name'
      },
      value: '',
      name: 'BaseInput',
      item: {
        attr: {
          placeholder: 'common.enter'
        }
      }
    }
  ]
}

export const comment = {
  attr: {
    'label-position': 'top'
  },
  formItems: [
    {
      attr: {
        label: 'comment.text',
        prop: 'comment_text'
      },
      value: '',
      name: 'BaseInput',
      item: {
        attr: {
          placeholder: 'common.enter'
        }
      }
    },
    {
      attr: {
        label: 'goods.name',
        prop: 'product_name'
      },
      value: '',
      name: 'BaseInput',
      item: {
        attr: {
          placeholder: 'common.enter'
        }
      }
    },
    {
      attr: {
        label: 'comment.score',
        prop: 'comment_score',
      },
      value: 0,
      name: 'BaseInputNumber',
      item: {
        attr: {
          min: 0,
          max: 5
        }
      }
    },
    {
      attr: {
        prop: 'time',
        label: 'comment.time'
      },
      value: [],
      name: 'BaseDatePicker',
      item: {
        attr: {
          type: 'datetimerange',
          'value-format': 'yyyy-MM-dd HH:mm:ss',
          'start-placeholder': 'common.start',
          'end-placeholder': 'common.end'
        }
      }
    }
  ]
}

/** 记录查询-卡牌多用户抽取检测 */
export const multiDrawCheck = {
  attr: {
    'label-position': 'top',
  },
  formItems: [
    {
      attr: {
        label: 'multiDraw.cardSortId',
        prop: 'card_sort_id'
      },
      value: '',
      name: 'BaseInput',
      item: {
        attr: {
          placeholder: 'common.enter',
          clearable: true
        }
      }
    },
    {
      attr: {
        label: 'multiDraw.cardId',
        prop: 'card_id'
      },
      value: '',
      name: 'BaseInput',
      item: {
        attr: {
          placeholder: 'common.enter',
          clearable: true
        }
      }
    },
    {
      attr: {
        label: 'multiDraw.cardName',
        prop: 'card_name'
      },
      value: '',
      name: 'BaseInput',
      item: {
        attr: {
          placeholder: 'common.enter',
          clearable: true
        }
      }
    },
    {
      attr: {
        label: 'multiDraw.userId',
        prop: 'user_id'
      },
      value: '',
      name: 'BaseInput',
      item: {
        attr: {
          placeholder: 'common.enter',
          clearable: true
        }
      }
    },
    {
      attr: {
        label: 'multiDraw.userName',
        prop: 'user_name'
      },
      value: '',
      name: 'BaseInput',
      item: {
        attr: {
          placeholder: 'common.enter',
          clearable: true
        }
      }
    },
    {
      attr: {
        label: 'multiDraw.userEmail',
        prop: 'user_email'
      },
      value: '',
      name: 'BaseInput',
      item: {
        attr: {
          placeholder: 'common.enter',
          clearable: true
        }
      }
    },
    {
      attr: {
        prop: 'time',
        label: 'multiDraw.time'
      },
      value: [],
      name: 'BaseDatePicker',
      item: {
        attr: {
          type: 'datetimerange',
          'value-format': 'yyyy-MM-dd HH:mm:ss',
          'start-placeholder': 'common.start',
          'end-placeholder': 'common.end'
        }
      }
    }
  ]
}

export const news = {
  attr: {
    'label-position': 'top',
  },
  formItems: [
    {
      attr: {
        label: 'news.img',
        prop: 'news_img'
      },
      value: '',
      name: 'baseFile',
      item: {
        attr: {
          limit: 1
        }
      }
    },
    {
      attr: {
        label: 'news.zh_title',
        prop: 'news_title'
      },
      value: '',
      name: 'BaseInput',
      item: {
        attr: {
          placeholder: 'common.enter'
        }
      }
    },
    {
      attr: {
        label: 'news.en_title',
        prop: 'en_news_title'
      },
      value: '',
      name: 'BaseInput',
      item: {
        attr: {
          placeholder: 'common.enter'
        }
      }
    },
    {
      attr: {
        label: 'news.zh_text',
        prop: 'news_text'
      },
      value: '',
      name: 'QuillEditor'
    },
    {
      attr: {
        label: 'news.en_text',
        prop: 'en_news_text'
      },
      value: '',
      name: 'QuillEditor'
    },
    {
      attr: {
        label: 'common.status',
        prop: 'news_status'
      },
      value: 1,
      name: 'BaseSelect',
      item: {
        attr: {
          placeholder: 'common.selectPh',
          disabled: true
        },
        options: [
          { label: 'common.normal', value: 1 },
          { label: 'common.disabled', value: 0 }
        ]
      }
    }
  ]
}

export const log = {
  attr: {
    'label-position': 'top',
  },
  formItems: [
    {
      attr: {
        label: 'coupon.admin',
        prop: 'admin_name'
      },
      value: '',
      name: 'BaseInput',
      item: {
        attr: {
          placeholder: 'common.enter'
        }
      }
    },
    {
      attr: {
        label: 'log.type',
        prop: 'activity_type'
      },
      value: '',
      name: 'BaseSelect',
      item: {
        attr: {
          placeholder: 'common.selectPh',
          clearable: true
        },
        options: [1, 2, 3, 4, 5, 9, 10, 11, 12, 13].map(value => ({ label: 'log.t' + value, value }))
      }
    },
    {
      attr: {
        prop: 'time',
        label: 'log.date'
      },
      value: [],
      name: 'BaseDatePicker',
      item: {
        attr: {
          type: 'datetimerange',
          'value-format': 'yyyy-MM-dd HH:mm:ss',
          'start-placeholder': 'common.start',
          'end-placeholder': 'common.end'
        }
      }
    }
  ]
}

export const userCons = {
  attr: {
    'label-position': 'top'
  },
  formItems: [
    {
      attr: {
        label: 'user.name',
        prop: 'user_name'
      },
      value: '',
      name: 'BaseInput',
      item: {
        attr: {
          placeholder: 'common.enter'
        }
      }
    },
    {
      attr: {
        prop: 'time',
        label: 'common.date'
      },
      value: [],
      name: 'BaseDatePicker',
      item: {
        attr: {
          type: 'datetimerange',
          'value-format': 'yyyy-MM-dd HH:mm:ss',
          'start-placeholder': 'common.start',
          'end-placeholder': 'common.end'
        }
      }
    }
  ]
}

export const orderCons = {
  attr: {
    'label-position': 'top'
  },
  formItems: [
    {
      attr: {
        label: 'goods.name',
        prop: 'product_name'
      },
      value: '',
      name: 'BaseInput',
      item: {
        attr: {
          placeholder: 'common.enter'
        }
      }
    },
    {
      attr: {
        label: 'analysis.based',
        prop: 'fieldName'
      },
      value: '',
      name: 'BaseSelect',
      item: {
        attr: {
          placeholder: 'common.selectPh',
          clearable: true
        },
        options: [
          { label: 'goods.stock', value: 'product_stock' },
          { label: 'analysis.browse', value: 'browse_number' },
          { label: 'analysis.collect', value: 'user_collect_number' },
          { label: 'analysis.sale_number', value: 'sale_number' },
          { label: 'db.sales_volume', value: 'sale_money' },
        ]
      }
    },
    {
      attr: {
        label: 'analysis.sort',
        prop: 'sort'
      },
      value: '',
      name: 'BaseSelect',
      item: {
        attr: {
          placeholder: 'common.selectPh',
          clearable: true
        },
        options: [
          { label: 'analysis.desc', value: 'desc' },
          { label: 'analysis.asc', value: 'asc' }
        ]
      }
    },
    {
      attr: {
        prop: 'time',
        label: 'common.date'
      },
      value: [],
      name: 'BaseDatePicker',
      item: {
        attr: {
          type: 'datetimerange',
          'value-format': 'yyyy-MM-dd HH:mm:ss',
          'start-placeholder': 'common.start',
          'end-placeholder': 'common.end'
        }
      }
    }
  ]
}
export const level = {
  attr: {
    'label-position': 'top'
  },
  formItems: [
    {
      attr: {
        label: 'level.img',
        prop: 'member_img'
      },
      value: '',
      name: 'upload',
      item: {
        attr: {
          accept: 'image/*'
        }
      }
    },
    {
      attr: {
        label: 'level.zh_name',
        prop: 'member_name'
      },
      value: '',
      name: 'BaseInput',
      item: {
        attr: {
          placeholder: 'common.enter'
        }
      }
    },
    {
      attr: {
        label: 'level.en_name',
        prop: 'en_member_name'
      },
      value: '',
      name: 'BaseInput',
      item: {
        attr: {
          placeholder: 'common.enter'
        }
      }
    },
    {
      attr: {
        label: 'level.start',
        prop: 'member_start',
      },
      value: '0',
      name: 'BaseInputNumber',
      item: {
        attr: {
          min: 0
        }
      }
    },
    {
      attr: {
        label: 'level.end',
        prop: 'member_end',
      },
      value: '0',
      name: 'BaseInputNumber',
      item: {
        attr: {
          min: 0
        }
      }
    },
    {
      attr: {
        label: 'level.loginBonusCoin',
        prop: 'login_bonus_coin'
      },
      value: 0,
      name: 'BaseInputNumber',
      item: {
        attr: {
          min: 0
        }
      }
    },
    {
      attr: {
        label: 'level.loginBonusDiamond',
        prop: 'login_bonus_diamond'
      },
      value: 0,
      name: 'BaseInputNumber',
      item: {
        attr: {
          min: 0
        }
      }
    },
    {
      attr: {
        label: 'common.status',
        prop: 'member_status'
      },
      value: 1,
      name: 'BaseSelect',
      item: {
        attr: {
          placeholder: 'common.selectPh',
          clearable: true
        },
        options: [
          { label: 'common.normal', value: 1 },
          { label: 'common.disabled', value: 0 }
        ]
      }
    }
  ]
}

export const classificationList = {
  SFC: {
    attr: {
      'label-position': 'top'
    },
    formItems: [
      {
        attr: {
          label: 'goodsCate.name',
          prop: 'card_sort_name'
        },
        value: '',
        name: 'BaseInput',
        item: {
          attr: {
            placeholder: 'common.enter'
          }
        }
      },
      {
        attr: {
          label: 'common.status',
          prop: 'status'
        },
        value: '',
        name: 'BaseSelect',
        item: {
          attr: {
            placeholder: 'common.selectPh',
            clearable: true
          },
          options: [
            { label: 'common.normal', value: 1 },
            { label: 'common.disabled', value: 0 }
          ]
        }
      }
    ]
  },
  EFC: {
    attr: {
      'label-position': 'top'
    },
    formItems: [
      {
        attr: {
          label: 'common.image',
          prop: 'card_sort_img'
        },
        value: '',
        name: 'baseFile',
        tip: '建议尺寸854*640px',
        item: {
          attr: {
            limit: 1
          }
        }
      },
      {
        attr: {
          label: 'goodsCate.zh_name',
          prop: 'card_sort_name'
        },
        value: '',
        name: 'BaseInput',
        item: {
          attr: {
            placeholder: 'common.enter'
          }
        }
      },
      {
        attr: {
          label: 'classificationList.sort',
          prop: 'sort'
        },
        value: 0,
        name: 'BaseInputNumber',
        item: {
          attr: {
            min: 0,
            step: 1,
            placeholder: 'classificationList.sortPh'
          }
        }
      },
      {
        attr: {
          label: 'classificationList.drawPrice',
          prop: 'card_draw_price'
        },
        value: 0,
        name: 'BaseInputNumber',
        item: {
          attr: {
            min: 0
          }
        }
      },
      {
        attr: {
          label: 'classificationList.drawLimit',
          prop: 'draw_limit_per_user'
        },
        value: 0,
        name: 'BaseInputNumber',
        tip: '0 表示不限制；填大于 0 的数字时，每个用户在该卡池累计最多可抽 N 张卡牌（单抽=1张、10连=10张、100连=100张），用完后不可再抽。',
        item: {
          attr: {
            min: 0,
            step: 1,
            placeholder: 'classificationList.drawLimitPh'
          }
        }
      },
      {
        attr: {
          label: 'classificationList.dailyDrawLimit',
          prop: 'daily_draw_limit'
        },
        value: 0,
        name: 'BaseInputNumber',
        tip: '0 表示不限制；填大于 0 的数字时，每个用户每天最多可抽 N 张卡牌（单抽=1张、10连=10张、100连=100张），每天 0 点自动重置。与「抽取上限」同时生效（两个都满足才能抽）。',
        item: {
          attr: {
            min: 0,
            step: 1,
            placeholder: 'classificationList.dailyDrawLimitPh'
          }
        }
      },
      {
        attr: {
          label: 'classificationList.memberLevelLimit',
          prop: 'member_level_limits'
        },
        value: [],
        name: 'BaseMemberLevelLimit',
        tip: '默认不限制；可多选会员等级，并分别为每个等级设置最多可抽张数（0 = 该等级不额外限制）。未勾选的等级不可抽取此卡池。',
        item: {
          options: []
        }
      },
      {
        attr: {
          label: 'classificationList.urlSingle',
          prop: 'card_sort_url_single'
        },
        value: '',
        name: 'FileSelectBtn'
      },
      {
        attr: {
          label: 'classificationList.urlTen',
          prop: 'card_sort_url_ten'
        },
        value: '',
        name: 'FileSelectBtn'
      },
      {
        attr: {
          label: 'classificationList.urlHundred',
          prop: 'card_sort_url_hundred'
        },
        value: '',
        name: 'FileSelectBtn'
      },
      {
        attr: {
          label: 'classificationList.brand',
          prop: 'card_brands'
        },
        // 新建卡池默認品牌 Lucku（編輯時由 echo 覆蓋為已存品牌）
        value: ['Lucku'],
        name: 'BaseSelect',
        item: {
          attr: {
            placeholder: 'classificationList.brandPh',
            multiple: true,
            filterable: true,
            allowCreate: true,
            defaultFirstOption: true,
            clearable: true
          }
        }
      },
      {
        attr: {
          label: 'common.status',
          prop: 'status'
        },
        value: 1,
        name: 'BaseSelect',
        item: {
          attr: {
            placeholder: 'common.selectPh',
            clearable: true
          },
          options: [
            { label: 'common.normal', value: 1 },
            { label: 'common.disabled', value: 0 }
          ]
        }
      }
    ]
  }
}

export const cardList = {
  SFC: {
    attr: {
      'label-position': 'top'
    },
    formItems: [
      {
        attr: {
          label: 'cardList.card_name',
          prop: 'card_name'
        },
        value: '',
        name: 'BaseInput',
        item: {
          attr: {
            placeholder: 'common.enter'
          }
        }
      }
    ]
  },
  EFC: {
    flex: true,
    attr: {
      'label-position': 'top'
    },
    formItems: [
      [
        {
          attr: {
            label: 'goods.image',
            prop: 'card_img'
          },
          value: '',
          name: 'baseFile',
          item: {
            attr: {
              limit: 1
            }
          }
        }
      ],
      [
        {
          attr: {
            label: 'goods.zh_name',
            prop: 'card_name'
          },
          value: '',
          name: 'BaseInput',
          item: {
            attr: {
              placeholder: 'common.enter'
            }
          }
        },
        {
          attr: {
            label: 'goods.en_name',
            prop: 'en_card_name'
          },
          value: '',
          name: 'BaseInput',
          item: {
            attr: {
              placeholder: 'common.enter'
            }
          }
        }
      ],
      [
        {
          attr: {
            label: 'goods.cate',
            prop: 'card_sort_id'
          },
          value: '',
          name: 'BaseSelect',
          item: {
            attr: {
              placeholder: 'common.selectPh',
              clearable: true
            },
            options: []
          }
        }
      ],
      [
        {
          attr: {
            label: 'goods.price',
            prop: 'card_price'
          },
          value: 0,
          name: 'BaseInputNumber',
          item: {
            attr: {
              min: 0
            }
          }
        },
        {
          attr: {
            label: 'goods.cost',
            prop: 'card_cost'
          },
          value: 0,
          name: 'BaseInputNumber',
          item: {
            attr: {
              min: 0
            }
          }
        }
      ],
      [
        {
          attr: {
            label: 'goods.zh_syno',
            prop: 'card_describe'
          },
          value: '',
          name: 'BaseInput',
          item: {
            attr: {
              placeholder: 'common.enter'
            }
          }
        },
        {
          attr: {
            label: 'goods.en_syno',
            prop: 'en_card_describe'
          },
          value: '',
          name: 'BaseInput',
          item: {
            attr: {
              placeholder: 'common.enter'
            }
          }
        }
      ],
      [
        {
          attr: {
            label: 'goods.status',
            prop: 'card_status'
          },
          value: 1,
          name: 'BaseSelect',
          item: {
            attr: {
              placeholder: 'common.selectPh',
              clearable: true
            },
            options: [
              { label: 'common.normal', value: 1 },
              { label: 'common.disabled', value: 0 }
            ]
          }
        },
        {
          attr: {
            label: 'cardList.isPriority',
            prop: 'is_priority'
          },
          value: 1,
          name: 'BaseSelect',
          item: {
            attr: {
              placeholder: 'common.selectPh',
              clearable: true
            },
            options: [
              { label: 'cardList.isPriority1', value: 1 },
              { label: 'cardList.isPriority0', value: 0 }
            ]
          }
        }
      ]
    ]
  }
}

/** 记录查询-用户充值记录 */
export const rechargeRecord = {
  attr: {
    'label-position': 'top',
  },
  formItems: [
    {
      attr: {
        label: 'recharge.userName',
        prop: 'user_name'
      },
      value: '',
      name: 'BaseInput',
      item: {
        attr: {
          placeholder: 'common.enter',
          clearable: true
        }
      }
    },
    {
      attr: {
        label: 'common.email',
        prop: 'user_email'
      },
      value: '',
      name: 'BaseInput',
      item: {
        attr: {
          placeholder: 'common.enter',
          clearable: true
        }
      }
    },
    {
      attr: {
        label: 'recharge.tradeNo',
        prop: 'trade_no'
      },
      value: '',
      name: 'BaseInput',
      item: {
        attr: {
          placeholder: 'common.enter',
          clearable: true
        }
      }
    },
    {
      attr: {
        label: 'recharge.paymentMode',
        prop: 'payment_mode'
      },
      value: '',
      name: 'BaseSelect',
      item: {
        attr: {
          placeholder: 'common.selectPh',
          clearable: true
        },
        options: [1, 2, 3].map(value => ({ label: 'recharge.m' + value, value }))
      }
    },
    {
      attr: {
        prop: 'time',
        label: 'recharge.time'
      },
      value: [],
      name: 'BaseDatePicker',
      item: {
        attr: {
          type: 'datetimerange',
          'value-format': 'yyyy-MM-dd HH:mm:ss',
          'start-placeholder': 'common.start',
          'end-placeholder': 'common.end'
        }
      }
    }
  ]
}

/** 记录查询-邀请码使用记录 */
export const invitationCodeLog = {
  attr: {
    'label-position': 'top',
  },
  formItems: [
    {
      attr: {
        label: 'invitationCodeLog.code',
        prop: 'code'
      },
      value: '',
      name: 'BaseInput',
      item: {
        attr: {
          placeholder: 'common.enter',
          clearable: true
        }
      }
    },
    {
      attr: {
        label: 'invitationCodeLog.userName',
        prop: 'user_name'
      },
      value: '',
      name: 'BaseInput',
      item: {
        attr: {
          placeholder: 'common.enter',
          clearable: true
        }
      }
    },
    {
      attr: {
        label: 'common.email',
        prop: 'user_email'
      },
      value: '',
      name: 'BaseInput',
      item: {
        attr: {
          placeholder: 'common.enter',
          clearable: true
        }
      }
    },
    {
      attr: {
        prop: 'time',
        label: 'invitationCodeLog.time'
      },
      value: [],
      name: 'BaseDatePicker',
      item: {
        attr: {
          type: 'datetimerange',
          'value-format': 'yyyy-MM-dd HH:mm:ss',
          'start-placeholder': 'common.start',
          'end-placeholder': 'common.end'
        }
      }
    }
  ]
}

/** 记录查询-金币兑换记录 */
export const diamondExchangeLog = {
  attr: {
    'label-position': 'top',
  },
  formItems: [
    {
      attr: {
        label: 'diamondExchangeLog.userName',
        prop: 'user_name'
      },
      value: '',
      name: 'BaseInput',
      item: {
        attr: {
          placeholder: 'common.enter',
          clearable: true
        }
      }
    },
    {
      attr: {
        label: 'common.email',
        prop: 'user_email'
      },
      value: '',
      name: 'BaseInput',
      item: {
        attr: {
          placeholder: 'common.enter',
          clearable: true
        }
      }
    },
    {
      attr: {
        prop: 'time',
        label: 'diamondExchangeLog.time'
      },
      value: [],
      name: 'BaseDatePicker',
      item: {
        attr: {
          type: 'datetimerange',
          'value-format': 'yyyy-MM-dd HH:mm:ss',
          'start-placeholder': 'common.start',
          'end-placeholder': 'common.end'
        }
      }
    }
  ]
}

/** 记录查询-抽卡批次记录 */
export const drawRecord = {
  attr: {
    'label-position': 'top',
  },
  formItems: [
    {
      attr: {
        label: 'draw.taskId',
        prop: 'task_id'
      },
      value: '',
      name: 'BaseInput',
      item: {
        attr: {
          placeholder: 'common.enter',
          clearable: true
        }
      }
    },
    {
      attr: {
        label: 'draw.userName',
        prop: 'user_name'
      },
      value: '',
      name: 'BaseInput',
      item: {
        attr: {
          placeholder: 'common.enter',
          clearable: true
        }
      }
    },
    {
      attr: {
        label: 'common.email',
        prop: 'user_email'
      },
      value: '',
      name: 'BaseInput',
      item: {
        attr: {
          placeholder: 'common.enter',
          clearable: true
        }
      }
    },
    {
      attr: {
        label: 'draw.cardSortId',
        prop: 'card_sort_id'
      },
      value: '',
      name: 'BaseInput',
      item: {
        attr: {
          placeholder: 'common.enter',
          clearable: true
        }
      }
    },
    {
      attr: {
        label: 'draw.status',
        prop: 'status'
      },
      value: '',
      name: 'BaseSelect',
      item: {
        attr: {
          placeholder: 'common.selectPh',
          clearable: true
        },
        options: [0, 1, 2].map(value => ({ label: 'draw.st' + value, value }))
      }
    },
    {
      attr: {
        prop: 'time',
        label: 'draw.time'
      },
      value: [],
      name: 'BaseDatePicker',
      item: {
        attr: {
          type: 'datetimerange',
          'value-format': 'yyyy-MM-dd HH:mm:ss',
          'start-placeholder': 'common.start',
          'end-placeholder': 'common.end'
        }
      }
    }
  ]
}

/** 查账-抽卡对账 */
export const drawAudit = {
  attr: {
    'label-position': 'top',
  },
  formItems: [
    {
      attr: {
        label: 'audit.taskId',
        prop: 'task_id'
      },
      value: '',
      name: 'BaseInput',
      item: {
        attr: {
          placeholder: 'common.enter',
          clearable: true
        }
      }
    },
    {
      attr: {
        label: 'audit.userName',
        prop: 'user_name'
      },
      value: '',
      name: 'BaseInput',
      item: {
        attr: {
          placeholder: 'common.enter',
          clearable: true
        }
      }
    },
    {
      attr: {
        label: 'common.email',
        prop: 'user_email'
      },
      value: '',
      name: 'BaseInput',
      item: {
        attr: {
          placeholder: 'common.enter',
          clearable: true
        }
      }
    },
    {
      attr: {
        label: 'audit.cardSortId',
        prop: 'card_sort_id'
      },
      value: '',
      name: 'BaseInput',
      item: {
        attr: {
          placeholder: 'common.enter',
          clearable: true
        }
      }
    },
    {
      attr: {
        label: 'audit.buildStatus',
        prop: 'status'
      },
      value: '',
      name: 'BaseSelect',
      item: {
        attr: {
          placeholder: 'common.selectPh',
          clearable: true
        },
        options: [0, 1, 2].map(value => ({ label: 'audit.status' + value, value }))
      }
    },
    {
      attr: {
        prop: 'time',
        label: 'audit.time'
      },
      value: [],
      name: 'BaseDatePicker',
      item: {
        attr: {
          type: 'datetimerange',
          'value-format': 'yyyy-MM-dd HH:mm:ss',
          'start-placeholder': 'common.start',
          'end-placeholder': 'common.end'
        }
      }
    }
  ]
}

/** 记录查询-抽卡明细记录 */
export const drawDetail = {
  attr: {
    'label-position': 'top',
  },
  formItems: [
    {
      attr: {
        label: 'drawDetail.orderNo',
        prop: 'order_no'
      },
      value: '',
      name: 'BaseInput',
      item: {
        attr: {
          placeholder: 'common.enter',
          clearable: true
        }
      }
    },
    {
      attr: {
        label: 'drawDetail.userName',
        prop: 'user_name'
      },
      value: '',
      name: 'BaseInput',
      item: {
        attr: {
          placeholder: 'common.enter',
          clearable: true
        }
      }
    },
    {
      attr: {
        label: 'common.email',
        prop: 'user_email'
      },
      value: '',
      name: 'BaseInput',
      item: {
        attr: {
          placeholder: 'common.enter',
          clearable: true
        }
      }
    },
    {
      attr: {
        label: 'drawDetail.cardName',
        prop: 'card_name'
      },
      value: '',
      name: 'BaseInput',
      item: {
        attr: {
          placeholder: 'common.enter',
          clearable: true
        }
      }
    },
    {
      attr: {
        label: 'drawDetail.cardSortId',
        prop: 'card_sort_id'
      },
      value: '',
      name: 'BaseInput',
      item: {
        attr: {
          placeholder: 'common.enter',
          clearable: true
        }
      }
    },
    {
      attr: {
        prop: 'time',
        label: 'drawDetail.time'
      },
      value: [],
      name: 'BaseDatePicker',
      item: {
        attr: {
          type: 'datetimerange',
          'value-format': 'yyyy-MM-dd HH:mm:ss',
          'start-placeholder': 'common.start',
          'end-placeholder': 'common.end'
        }
      }
    }
  ]
}
