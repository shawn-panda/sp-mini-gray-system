-- Copyright (C) shawnpanda.com
-- Author: ShawnPanda
-- Date: 2016/07/26

local modulename = "helper"
local _M = {}
      _M._VERSION = '0.0.1'

-- 获取灰度标记
_M.getFlagBySource = function(self, source, key)
    -- 1. source:uri
    if 'uri' == source then
        return ngx.req.get_uri_args()[key]
    end

    -- 2. source:host
    if 'host' == source then
        return self:getDomainHeaderName()
    end

    -- 3. source:cookie
    if 'cookie' == source then
        return self:getCookie()[key]
    end

    -- 4. source:ip
    if 'ip' == source then
        local ip = self:getClientIP()
        return self:ip2long(ip)
    end

    -- 5. source:random
    if 'random' == source then
        return self:getRandomNumber()
    end

    return nil
end

-- 校验灰度标记
_M.verifyFlagByType = function(self, type, flag, value)
     -- 1. type:int
    if 'int' == type then
        return tonumber(value) == tonumber(flag)
    end

    -- 2. type:range from {min} to {max}
    if 'range' == type then
        return self:findInRange(value, flag)
    end

    -- 3. type:set
    if 'set' == type then
        return self:findInSet(value, flag)
    end

    -- 4. type:threshold
    if 'threshold' == type then
        return tonumber(value) > tonumber(flag)
    end

    return false
end

-- 字符串分隔
_M.split = function(self, str, delimiter)
    local t = {}
    while (true) do
        local pos = string.find(str, delimiter)
        if not pos then
            local size_t = table.getn(t)
            table.insert(t,size_t+1,str)
            break
        end

        local sub_str = string.sub(str, 1, pos - 1)
        local size_t = table.getn(t)
        table.insert(t,size_t+1,sub_str)
        local size_s = string.len(str)
        str = string.sub(str, pos + 1, size_s)
    end

    return t
end 

-- 集合中查找
_M.findInSet = function(self, str, flag)
    local s = ',' .. str .. ','
    local p = ',' .. flag .. ','

    return nil ~= string.find(s, p)
end

-- 范围内查找
_M.findInRange = function(self, str, flag)
    local min_value = 0
    local max_value = 0
    local range = self.split(str, ',')
    if 2 == table.getn(range) then
        min_value = tonumber(range[1])
        max_value = tonumber(range[2])
    end

    return min_value < tonumber(flag) and max_value > tonumber(flag)
end

-- 获取随机数
_M.getRandomNumber = function(self)
    math.randomseed(tostring(os.time()):reverse():sub(1, 6))
    return math.random(0, 99)
end

-- 获取Cookie
_M.getCookie = function(self)
    local cookie = {}
    if ngx.var.http_cookie then
        s = ngx.var.http_cookie
        for k, v in string.gmatch(s, "([%w_]+)=([%w%/%.=_-]+)") do
            cookie[k] = v
        end
    end
    return cookie
end

-- 获取IP
_M.getClientIP = function(self)
    local ip = ngx.req.get_headers()['X-Real-IP']
    if nil == ip then
        ip = ngx.req.get_headers()['X_Forwarded_For']
    end
    if nil == ip then
        ip = ngx.req.get_headers()['Proxy-Client-IP']
    end
    if nil == ip then
        ip = ngx.var.remote_addr
    end
    return ip
end

-- 获取域名头
_M.getDomainHeaderName = function(self)
    local http_info   = self:split(ngx.var.http_host, '%.')
    local pieces      = table.getn(http_info)
    local header_name = nil

    if 3 == pieces then
        header_name = http_info[1]
    end

    if 4 == pieces then
        header_name = http_info[2]
    end

    return header_name
end

--ip转整数
_M.ip2long = function(self, ip)
  local ips = self:split(ip, '%.')
  local num = 0
  for i,v in pairs(ips) do
    num = num +(tonumber(v) * math.pow(256,#ips-i)) 
  end
  return num
end

return _M
