<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
  <xsl:template match="form">
    <xsl:param name="title" select="@title" />
    <xsl:if test="$title">
      <h2>
        <xsl:value-of select="$title" />
      </h2>
    </xsl:if>
    <form method="{@method}" action="{@action}" enctype="{@enctype}" class="{@class}">
      <xsl:apply-templates select="control" />
    </form>
  </xsl:template>


  <!-- группа вложенных контролов -->
  <xsl:template match="control[@type = 'fieldset']">
    <xsl:if test="count(control)">
      <fieldset class="{@class}" id="{@id}">
        <legend>
          <span class="title">
            <xsl:value-of select="@label" />
          </span>
        </legend>
        <xsl:apply-templates select="control" />
      </fieldset>
    </xsl:if>
  </xsl:template>


  <!-- обычные текстовые строки -->
  <xsl:template match="control[@type = 'email' or @type = 'textline' or @type = 'url' or @type = 'date' or @type = 'datetime' or @type = 'number' or @type = 'list']">
    <div>
      <xsl:call-template name="default_control_classes" />
      <xsl:call-template name="default_control_label" />
      <input type="text" class="{@class}" name="{@name}" value="{@value}" maxlength="{@maxlength}" id="{@id}" />
      <xsl:call-template name="default_control_info" />
    </div>
  </xsl:template>


  <!-- пароль -->
  <xsl:template match="control[@type = 'passwordedit']">
    <div>
      <xsl:call-template name="default_control_classes" />
      <xsl:call-template name="default_control_label" />
      <input type="password" class="{@class}" name="{@name}[]" id="{@id}" />
      <span>
        <xsl:text>подтверждение:</xsl:text>
      </span>
      <input type="password" class="{@class}" name="{@name}[]" />
      <xsl:call-template name="default_control_info" />
    </div>
  </xsl:template>


  <!-- пароль -->
  <xsl:template match="control[@type = 'password']">
    <div>
      <xsl:call-template name="default_control_classes" />
      <xsl:call-template name="default_control_label" />
      <input type="password" class="{@class}" name="{@name}" id="{@id}" />
      <xsl:call-template name="default_control_info" />
    </div>
  </xsl:template>
  <!-- скрытое поле -->
  <xsl:template match="control[@type = 'hidden']">
    <input type="hidden" name="{@name}" value="{@value}" />
  </xsl:template>


  <!-- выпадающий список -->
  <xsl:template match="control[@type = 'enum' or @type = 'section']">
    <div>
      <xsl:call-template name="default_control_classes" />
      <xsl:call-template name="default_control_label" />
      <select name="{@name}" id="{@id}">
        <xsl:for-each select="option">
          <option value="{@value}">
            <xsl:if test="@selected">
              <xsl:attribute name="selected">
                <xsl:text>selected</xsl:text>
              </xsl:attribute>
            </xsl:if>
            <xsl:value-of select="@text" disable-output-escaping="yes" />
          </option>
        </xsl:for-each>
      </select>
      <xsl:call-template name="default_control_info" />
    </div>
  </xsl:template>


  <!-- радио -->
  <xsl:template match="control[@type = 'enumradio']">
    <div>
      <xsl:call-template name="default_control_classes" />
      <fieldset>
        <xsl:if test="@label">
          <legend>
            <xsl:value-of select="@label" />
          </legend>
        </xsl:if>
        <xsl:for-each select="option">
          <label>
            <input type="radio" name="{../@name}" value="{@value}">
              <xsl:if test="@checked">
                <xsl:attribute name="checked">
                  <xsl:text>checked</xsl:text>
                </xsl:attribute>
              </xsl:if>
              <xsl:value-of select="@text" disable-output-escaping="yes" />
            </input>
          </label>
        </xsl:for-each>
      </fieldset>
      <xsl:call-template name="default_control_info" />
    </div>
  </xsl:template>


  <!-- группа чекбоксов -->
  <xsl:template match="control[@type = 'set']">
    <div>
      <xsl:call-template name="default_control_classes" />
      <xsl:call-template name="default_control_label" />
      <input type="hidden" name="{@name}[__reset]" value="1" />
      <xsl:apply-templates select="option" mode="set-control" />
      <xsl:call-template name="default_control_info" />
    </div>
  </xsl:template>

  <xsl:template match="option" mode="set-control">
    <div class="form-checkbox">
      <label class="normal">
        <input type="checkbox" value="{@value}" name="{../@name}[]">
          <xsl:if test="@checked">
            <xsl:attribute name="checked">
              <xsl:text>checked</xsl:text>
            </xsl:attribute>
          </xsl:if>
        </input>
        <xsl:value-of select="@text" disable-output-escaping="yes" />
      </label>
    </div>
  </xsl:template>


  <!-- чекбокс -->
  <xsl:template match="control[@type = 'bool']">
    <div>
      <xsl:call-template name="default_control_classes" />
      <label>
        <input type="checkbox" name="{@name}" value="{@value}">
          <xsl:if test="@checked">
            <xsl:attribute name="checked">
              <xsl:text>checked</xsl:text>
            </xsl:attribute>
          </xsl:if>
        </input>
        <xsl:value-of select="@label" />
      </label>
      <xsl:call-template name="default_control_info" />
    </div>
  </xsl:template>


  <!-- привязка к разделам -->
  <xsl:template match="control[@type = 'sections']">
    <div>
      <xsl:call-template name="default_control_classes" />
      <xsl:call-template name="default_control_label" />
      <input type="hidden" name="{@name}[__reset]" value="1" />
      <xsl:apply-templates select="option" mode="set-control" />
      <xsl:call-template name="default_control_info" />
    </div>
  </xsl:template>


  <!-- кнопка для отправки формы -->
  <xsl:template match="control[@type = 'submit']">
    <div>
      <xsl:call-template name="default_control_classes" />
      <input type="submit" value="{@value}" />
    </div>
  </xsl:template>

  <xsl:template match="control[@type = 'textarea' or @type = 'texthtml' or @type='markdown']">
    <div>
      <xsl:call-template name="default_control_classes" />
      <xsl:call-template name="default_control_label" />
      <textarea class="{@class}" rows="{@rows}" cols="{@cols}" name="{@name}">
        <xsl:value-of select="text()" disable-output-escaping="yes" />
      </textarea>
      <xsl:call-template name="default_control_info" />
    </div>
  </xsl:template>


  <!-- файловый архив -->
  <xsl:template match="control[@type = 'attachment']">
    <div>
      <xsl:call-template name="default_control_classes" />
      <xsl:call-template name="default_control_label" />
      <!--
      <xsl:if test="@newfile">
      -->
        <input type="file" name="{@name}" />
      <!--
      </xsl:if>
      -->
      <xsl:call-template name="default_control_info" />
    </div>
  </xsl:template>


  <!-- управление правами -->
  <xsl:template match="control[@type = 'access' or @type = 'accessrev']">
    <div>
      <xsl:call-template name="default_control_classes" />
      <xsl:call-template name="default_control_label" />
      <input type="hidden" name="{@name}[__reset]" value="1" />
      <table class="padded highlight access">
        <xsl:if test="count(column) &gt; 1">
          <thead>
            <tr>
              <th/>
              <xsl:for-each select="column">
                <th>
                  <xsl:value-of select="@label" />
                </th>
              </xsl:for-each>
            </tr>
          </thead>
        </xsl:if>
        <tbody>
          <xsl:for-each select="row">
            <tr class="gid-{@id}">
              <td>
                <xsl:value-of select="@name" disable-output-escaping="yes" />
              </td>
              <xsl:for-each select="perm">
                <td>
                  <input type="checkbox" name="{../../@name}[{../@id}][{@name}]" class="perm-{@name}" value="1">
                    <xsl:if test="@enabled = 'yes'">
                      <xsl:attribute name="checked">
                        <xsl:text>checked</xsl:text>
                      </xsl:attribute>
                    </xsl:if>
                  </input>
                </td>
              </xsl:for-each>
            </tr>
          </xsl:for-each>
        </tbody>
      </table>
      <xsl:call-template name="default_control_info" />
    </div>
  </xsl:template>


  <!-- редактор полей -->
  <xsl:template match="control[@type = 'field']">
    <div>
      <xsl:call-template name="default_control_classes" />
      <xsl:call-template name="default_control_label" />
      <input type="hidden" name="{@name}[__reset]" value="1" />
      <table class="padded">
        <thead>
          <tr>
            <th>Имя</th>
            <th>Заголовок</th>
            <th>Группа</th>
            <th>Тип</th>
            <th>Справочник</th>
            <th>Подсказка</th>
            <th>Обязательно</th>
            <th>Удалить</th>
          </tr>
        </thead>
        <tbody>
          <xsl:for-each select="field">
            <tr>
              <td>
                <xsl:if test="@isnew">
                  <input type="text" name="{../@name}[{position()}][name]" />
                </xsl:if>
                <xsl:if test="not(@isnew)">
                  <input type="text" name="{../@name}[{position()}][name]" value="{@name}" />
                </xsl:if>
              </td>
              <td>
                <input type="text" name="{../@name}[{position()}][label]" value="{@label}" />
              </td>
              <td>
                <input type="text" name="{../@name}[{position()}][group]" value="{@group}" />
              </td>
              <td>
                <xsl:variable name="current" select="@type" />
                <select name="{../@name}[{position()}][type]">
                  <xsl:for-each select="../type">
                    <option value="{@name}">
                      <xsl:if test="@name = $current">
                        <xsl:attribute name="selected">
                          <xsl:text>selected</xsl:text>
                        </xsl:attribute>
                      </xsl:if>
                      <xsl:value-of select="@label" />
                    </option>
                  </xsl:for-each>
                </select>
              </td>
              <td>
                <xsl:variable name="current" select="@dictionary" />
                <select name="{../@name}[{position()}][dictionary]">
                  <option value="">
                    <xsl:text>(не используется)</xsl:text>
                  </option>
                  <xsl:for-each select="../dictionary">
                    <option value="{@name}">
                      <xsl:if test="@name = $current">
                        <xsl:attribute name="selected">
                          <xsl:text>selected</xsl:text>
                        </xsl:attribute>
                      </xsl:if>
                      <xsl:value-of select="@label" />
                    </option>
                  </xsl:for-each>
                </select>
              </td>
              <td>
                <input type="text" name="{../@name}[{position()}][description]" value="{@description}" />
              </td>
              <td>
                <input type="checkbox" name="{../@name}[{position()}][required]" value="1">
                  <xsl:if test="@required">
                    <xsl:attribute name="checked">
                      <xsl:text>checked</xsl:text>
                    </xsl:attribute>
                  </xsl:if>
                </input>
              </td>
              <td>
                <input type="checkbox" name="{../@name}[{position()}][delete]" value="1" />
              </td>
            </tr>
          </xsl:for-each>
        </tbody>
      </table>
      <u class="fakelink">Добавить поле</u>
      <xsl:call-template name="default_control_info" />
    </div>
  </xsl:template>


  <!-- связь с объектом -->
  <xsl:template match="control[@type = 'nodelink']">
    <div>
      <xsl:call-template name="default_control_classes" />
      <xsl:call-template name="default_control_label" />

      <xsl:choose>
        <xsl:when test="count(options/option)">
          <select name="{@name}">
            <xsl:if test="not(@required)">
              <option value=""></option>
            </xsl:if>
            <xsl:for-each select="options/option">
              <option value="{@value}">
                <xsl:if test="@selected">
                  <xsl:attribute name="selected">
                    <xsl:text>selected</xsl:text>
                  </xsl:attribute>
                </xsl:if>
                <xsl:value-of select="@text" />
                <xsl:if test="not(@text)">
                  <xsl:value-of select="@value" />
                </xsl:if>
              </option>
            </xsl:for-each>
          </select>
        </xsl:when>
        <xsl:otherwise>
          <input type="text" name="{@name}" value="{@value}" maxwidth="255" class="form-text autocomplete" />
        </xsl:otherwise>
      </xsl:choose>
      <xsl:call-template name="default_control_info" />
    </div>
  </xsl:template>


  <!-- стандартные классы для контрола -->
  <xsl:template name="default_control_classes">
    <xsl:attribute name="class">
      <xsl:text>control </xsl:text>
      <xsl:value-of select="@type" />
      <xsl:text>-wrapper </xsl:text>
      <xsl:value-of select="@name" />
      <xsl:text>-field-wrapper </xsl:text>
      <xsl:value-of select="@class" />
    </xsl:attribute>
  </xsl:template>

  <!-- стандартная подпись для контрола -->
  <xsl:template name="default_control_label">
    <xsl:if test="@label">
      <label>
        <xsl:if test="@id">
          <xsl:attribute name="for">
            <xsl:value-of select="@id" />
          </xsl:attribute>
        </xsl:if>
        <span>
          <xsl:value-of select="@label" />
          <xsl:text>:</xsl:text>
          <xsl:if test="@required">
            <span class="required">
              <xsl:text>*</xsl:text>
            </span>
          </xsl:if>
        </span>
      </label>
    </xsl:if>
  </xsl:template>

  <!-- стандартное описание контрола -->
  <xsl:template name="default_control_info">
    <xsl:if test="@description">
      <div class="note">
        <xsl:value-of select="@description" disable-output-escaping="yes" />
      </div>
    </xsl:if>
  </xsl:template>

  <!-- каркас контрола -->
  <!--
  <xsl:template match="control[@type = 'xyz']">
    <div>
      <xsl:call-template name="default_control_classes" />
      <xsl:call-template name="default_control_label" />

      <xsl:call-template name="default_control_info" />
    </div>
  </xsl:template>
  -->
</xsl:stylesheet>
