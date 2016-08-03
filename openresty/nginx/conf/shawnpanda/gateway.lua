-- Copyright (C) shawnpanda.com
-- Author: ShawnPanda
-- Date: 2016/07/25

ngx.header.content_type = "text/html"

local redis   = require('resty.redis')
local cache   = redis.new()
local ok, err = cache.connect(cache, '127.0.0.1', '6379')
                cache:set_timeout(60000)

if not ok then
    return
end

local timestamp   = os.time()
local helper      = require('lib.helper')
local prefix      = 'AB_TESTING_RULE'
local field_table = { 'source', 'type', 'key', 'value', 'start_timestamp', 'end_timestamp' }

local is_beta     = false

for i=0,7,1 do
    local table  = prefix .. tostring(i) 
    local result, flags, err = cache:hmget( table, unpack(field_table) )

    local source = result[1]
    local type   = result[2]
    local key    = result[3]
    local value  = result[4]
    local start_timestamp = tonumber(result[5])
    local end_timestamp   = tonumber(result[6])

    -- 模拟continue
    while true do
        -- 0. 判断规则有效性
        if null == source or ngx.null == source then
            break
        end

        -- 1. 获取标记
        local flag = helper:getFlagBySource(source, key)
        if nil == flag then
            break
        end

        -- 2. 判断标记有效性
        local is_valid = helper:compareFlagByType(type, flag, value)
        if not is_valid then
            break
        end

        -- 3. 判断时间有效性
        if timestamp > start_timestamp and timestamp < end_timestamp then
            is_beta = true
            break
        end
    end

    if is_beta then
      break
    end
end

cache:close()

if is_beta then
    ngx.var.backend = 'beta_server'
end

