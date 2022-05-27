<?php

namespace sketch\view;

class ViewBase
{
    /**
     * @param string $_file_
     * @param array $_params_
     * @return false|string
     */
    public function renderPhpFile(string $_file_, array $_params_ = [])
    {
        $_obInitialLevel_ = ob_get_level();
        ob_start();
        ob_implicit_flush(false);
        extract($_params_, EXTR_OVERWRITE);
        try {
            require $_file_;
            return ob_get_clean();
        } catch (\Exception $e) {
            while (ob_get_level() > $_obInitialLevel_) {
                if (!@ob_end_clean()) {
                    ob_clean();
                }
            }
            throw $e;
        } catch (\Throwable $e) {
            while (ob_get_level() > $_obInitialLevel_) {
                if (!@ob_end_clean()) {
                    ob_clean();
                }
            }
            throw $e;
        }
    }

    public function render($fileName, $params = [])
    {
        return $this->renderPhpFile($fileName, $params);
    }
}