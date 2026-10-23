<?php

return [

    /*
    |--------------------------------------------------------------------------
    | 验证语言行
    |--------------------------------------------------------------------------
    |
    | 以下语言行包含验证器类使用的默认错误消息。其中某些规则有多个版本，
    | 例如尺寸规则。你可以在此自由调整这些消息。
    |
    */

    'accepted' => ':attribute 必须接受。',
    'accepted_if' => '当 :other 为 :value 时，:attribute 必须接受。',
    'active_url' => ':attribute 必须是一个合法的 URL。',
    'after' => ':attribute 必须是 :date 之后的时间。',
    'after_or_equal' => ':attribute 必须是 :date 之后或相同的时间。',
    'alpha' => ':attribute 只能包含字母。',
    'alpha_dash' => ':attribute 只能包含字母、数字、短横线和下划线。',
    'alpha_num' => ':attribute 只能包含字母和数字。',
    'any_of' => ':attribute 无效。',
    'array' => ':attribute 必须是数组。',
    'array_keys' => ':attribute 必须包含指定的键：:values。',
    'ascii' => ':attribute 只能包含单字节的字母、数字和符号。',
    'base64' => ':attribute 必须是合法的 Base64 字符串。',
    'before' => ':attribute 必须是 :date 之前的时间。',
    'before_or_equal' => ':attribute 必须是 :date 之前或相同的时间。',
    'between' => [
        'array' => ':attribute 必须有 :min 到 :max 个元素。',
        'file' => ':attribute 必须介于 :min 到 :max KB 之间。',
        'numeric' => ':attribute 必须介于 :min 到 :max 之间。',
        'string' => ':attribute 必须介于 :min 到 :max 个字符之间。',
    ],
    'boolean' => ':attribute 必须为 true 或 false。',
    'can' => ':attribute 包含未经授权的值。',
    'confirmed' => ':attribute 两次输入不一致。',
    'contains' => ':attribute 缺少必需的值。',
    'current_password' => '当前密码不正确。',
    'date' => ':attribute 必须是一个合法的日期。',
    'date_equals' => ':attribute 必须是 :date。',
    'date_format' => ':attribute 的格式必须为 :format。',
    'decimal' => ':attribute 必须有 :decimal 位小数。',
    'declined' => ':attribute 必须拒绝。',
    'declined_if' => '当 :other 为 :value 时，:attribute 必须拒绝。',
    'different' => ':attribute 和 :other 必须不同。',
    'digits' => ':attribute 必须是 :digits 位数字。',
    'digits_between' => ':attribute 必须是 :min 到 :max 位数字。',
    'dimensions' => ':attribute 的图片尺寸不正确。',
    'distinct' => ':attribute 存在重复值。',
    'doesnt_contain' => ':attribute 不能包含以下任何一项：:values。',
    'doesnt_end_with' => ':attribute 不能以以下任何一项结尾：:values。',
    'doesnt_start_with' => ':attribute 不能以以下任何一项开头：:values。',
    'email' => ':attribute 必须是合法的邮箱地址。',
    'encoding' => ':attribute 必须使用 :encoding 编码。',
    'ends_with' => ':attribute 必须以以下之一结尾：:values。',
    'enum' => '所选的 :attribute 无效。',
    'exists' => '所选的 :attribute 无效。',
    'extensions' => ':attribute 的文件扩展名必须为以下之一：:values。',
    'file' => ':attribute 必须是文件。',
    'filled' => ':attribute 不能为空。',
    'gt' => [
        'array' => ':attribute 必须多于 :value 个元素。',
        'file' => ':attribute 必须大于 :value KB。',
        'numeric' => ':attribute 必须大于 :value。',
        'string' => ':attribute 必须大于 :value 个字符。',
    ],
    'gte' => [
        'array' => ':attribute 必须不少于 :value 个元素。',
        'file' => ':attribute 必须大于或等于 :value KB。',
        'numeric' => ':attribute 必须大于或等于 :value。',
        'string' => ':attribute 必须大于或等于 :value 个字符。',
    ],
    'hex_color' => ':attribute 必须是合法的十六进制颜色值。',
    'image' => ':attribute 必须是图片。',
    'in' => '所选的 :attribute 无效。',
    'in_array' => ':attribute 必须存在于 :other 中。',
    'in_array_keys' => ':attribute 必须至少包含以下键之一：:values。',
    'integer' => ':attribute 必须是整数。',
    'ip' => ':attribute 必须是合法的 IP 地址。',
    'ipv4' => ':attribute 必须是合法的 IPv4 地址。',
    'ipv6' => ':attribute 必须是合法的 IPv6 地址。',
    'json' => ':attribute 必须是合法的 JSON 字符串。',
    'list' => ':attribute 必须是列表。',
    'lowercase' => ':attribute 必须为小写。',
    'lt' => [
        'array' => ':attribute 必须少于 :value 个元素。',
        'file' => ':attribute 必须小于 :value KB。',
        'numeric' => ':attribute 必须小于 :value。',
        'string' => ':attribute 必须小于 :value 个字符。',
    ],
    'lte' => [
        'array' => ':attribute 不能多于 :value 个元素。',
        'file' => ':attribute 必须小于或等于 :value KB。',
        'numeric' => ':attribute 必须小于或等于 :value。',
        'string' => ':attribute 必须小于或等于 :value 个字符。',
    ],
    'mac_address' => ':attribute 必须是合法的 MAC 地址。',
    'max' => [
        'array' => ':attribute 不能多于 :max 个元素。',
        'file' => ':attribute 不能大于 :max KB。',
        'numeric' => ':attribute 不能大于 :max。',
        'string' => ':attribute 不能超过 :max 个字符。',
    ],
    'max_digits' => ':attribute 不能超过 :max 位数字。',
    'mimes' => ':attribute 的文件类型必须为 :values。',
    'mimetypes' => ':attribute 的文件类型必须为 :values。',
    'min' => [
        'array' => ':attribute 至少要有 :min 个元素。',
        'file' => ':attribute 不能小于 :min KB。',
        'numeric' => ':attribute 不能小于 :min。',
        'string' => ':attribute 不能少于 :min 个字符。',
    ],
    'min_digits' => ':attribute 至少要有 :min 位数字。',
    'missing' => ':attribute 必须为空。',
    'missing_if' => '当 :other 为 :value 时，:attribute 必须为空。',
    'missing_unless' => '除非 :other 为 :value，否则 :attribute 必须为空。',
    'missing_with' => '当 :values 存在时，:attribute 必须为空。',
    'missing_with_all' => '当 :values 都存在时，:attribute 必须为空。',
    'multiple_of' => ':attribute 必须是 :value 的倍数。',
    'not_in' => '所选的 :attribute 无效。',
    'not_regex' => ':attribute 的格式不正确。',
    'numeric' => ':attribute 必须是数字。',
    'password' => [
        'letters' => ':attribute 必须至少包含一个字母。',
        'mixed' => ':attribute 必须至少包含一个大写字母和一个小写字母。',
        'numbers' => ':attribute 必须至少包含一个数字。',
        'symbols' => ':attribute 必须至少包含一个符号。',
        'uncompromised' => '给定的 :attribute 已出现在数据泄露记录中，请更换一个 :attribute。',
    ],
    'present' => ':attribute 必须存在。',
    'present_if' => '当 :other 为 :value 时，:attribute 必须存在。',
    'present_unless' => '除非 :other 为 :value，否则 :attribute 必须存在。',
    'present_with' => '当 :values 存在时，:attribute 必须存在。',
    'present_with_all' => '当 :values 都存在时，:attribute 必须存在。',
    'prohibited' => ':attribute 禁止填写。',
    'prohibited_if' => '当 :other 为 :value 时，:attribute 禁止填写。',
    'prohibited_if_accepted' => '当 :other 被接受时，:attribute 禁止填写。',
    'prohibited_if_declined' => '当 :other 被拒绝时，:attribute 禁止填写。',
    'prohibited_unless' => '当 :other 不在 :values 中时，:attribute 禁止填写。',
    'prohibits' => ':attribute 禁止同时填写 :other。',
    'regex' => ':attribute 的格式不正确。',
    'required' => ':attribute 不能为空。',
    'required_array_keys' => ':attribute 必须包含以下条目：:values。',
    'required_if' => '当 :other 为 :value 时，:attribute 不能为空。',
    'required_if_accepted' => '当 :other 被接受时，:attribute 不能为空。',
    'required_if_declined' => '当 :other 被拒绝时，:attribute 不能为空。',
    'required_unless' => '当 :other 不在 :values 中时，:attribute 不能为空。',
    'required_with' => '当 :values 存在时，:attribute 不能为空。',
    'required_with_all' => '当 :values 都存在时，:attribute 不能为空。',
    'required_without' => '当 :values 不存在时，:attribute 不能为空。',
    'required_without_all' => '当 :values 都不存在时，:attribute 不能为空。',
    'same' => ':attribute 和 :other 必须一致。',
    'size' => [
        'array' => ':attribute 必须包含 :size 个元素。',
        'file' => ':attribute 必须为 :size KB。',
        'numeric' => ':attribute 必须为 :size。',
        'string' => ':attribute 必须为 :size 个字符。',
    ],
    'starts_with' => ':attribute 必须以以下之一开头：:values。',
    'string' => ':attribute 必须是字符串。',
    'timezone' => ':attribute 必须是合法的时区。',
    'unique' => ':attribute 已被占用。',
    'uploaded' => ':attribute 上传失败。',
    'uppercase' => ':attribute 必须为大写。',
    'url' => ':attribute 必须是合法的 URL。',
    'ulid' => ':attribute 必须是合法的 ULID。',
    'uuid' => ':attribute 必须是合法的 UUID。',

    /*
    |--------------------------------------------------------------------------
    | 自定义验证语言行
    |--------------------------------------------------------------------------
    |
    | 你可以按 "attribute.rule" 的命名规则为指定属性指定自定义语言行，
    | 从而快速为某个属性的某条规则定义专属提示消息。
    |
    */

    'custom' => [
        'attribute-name' => [
            'rule-name' => 'custom-message',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | 自定义验证属性
    |--------------------------------------------------------------------------
    |
    | 以下语言行用于将属性占位符替换为更友好的显示名称，例如将
    | "email" 显示为 "邮箱"，使提示消息更加易懂。
    |
    */

    'attributes' => [
        'name' => '姓名',
        'username' => '用户名',
        'email' => '邮箱',
        'password' => '密码',
        'password_confirmation' => '确认密码',
        'old_password' => '旧密码',
        'new_password' => '新密码',
        'current_password' => '当前密码',
        'phone' => '手机号',
        'telephone' => '电话',
        'mobile' => '手机号',
        'age' => '年龄',
        'sex' => '性别',
        'gender' => '性别',
        'birthday' => '生日',
        'date' => '日期',
        'time' => '时间',
        'address' => '地址',
        'city' => '城市',
        'province' => '省份',
        'country' => '国家',
        'zip' => '邮政编码',
        'title' => '标题',
        'subject' => '主题',
        'content' => '内容',
        'description' => '描述',
        'summary' => '摘要',
        'remark' => '备注',
        'remarks' => '备注',
        'message' => '消息',
        'status' => '状态',
        'type' => '类型',
        'category' => '分类',
        'avatar' => '头像',
        'image' => '图片',
        'file' => '文件',
        'code' => '验证码',
        'captcha' => '验证码',
        'quantity' => '数量',
        'price' => '价格',
        'amount' => '金额',
        'website' => '网站',
        'url' => 'URL',
    ],

];
