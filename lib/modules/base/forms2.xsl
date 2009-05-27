<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
  <!-- сама форма -->
  <xsl:template match="form">
    <xsl:param name="title" select="@title" />
    <xsl:if test="$title">
      <h2>
        <xsl:value-of select="$title" />
      </h2>
    </xsl:if>
    <form method="{@method}" action="{@action}" enctype="{@enctype}" class="{@class}">
      <xsl:apply-templates select="input" />
    </form>
  </xsl:template>

  <!-- группа вложенных контролов -->
  <xsl:template match="input[@type='group']">
    <xsl:if test="input">
      <fieldset>
        <xsl:if test="@tab">
          <xsl:attribute name="class">tab</xsl:attribute>
        </xsl:if>
        <xsl:if test="@title">
          <legend>
            <span>
              <xsl:value-of select="@title" />
            </span>
          </legend>
        </xsl:if>
        <div class="controls">
          <xsl:apply-templates select="input" />
        </div>
      </fieldset>
    </xsl:if>
  </xsl:template>

  <xsl:template match="input[@type='text']">
    <label class="control">
      <xsl:apply-templates select="." mode="label" />
      <input type="text" name="{@name}" value="{text()}" class="text" />
    </label>
    <xsl:apply-templates select="." mode="help" />
  </xsl:template>

  <xsl:template match="input[@type='password']">
    <label class="control">
      <xsl:apply-templates select="." mode="label" />
      <input type="password" name="{@name}" value="{@value}" class="text" />
    </label>
    <xsl:apply-templates select="." mode="help" />
  </xsl:template>

  <xsl:template match="input[@type='select']">
    <xsl:if test="option">
      <label class="control">
        <xsl:apply-templates select="." mode="label" />
        <select name="{@name}">
          <xsl:for-each select="option">
            <option value="{@value}">
              <xsl:if test="@selected">
                <xsl:attribute name="selected">selected</xsl:attribute>
              </xsl:if>
              <xsl:value-of select="text()" />
            </option>
          </xsl:for-each>
        </select>
      </label>
      <xsl:apply-templates select="." mode="help" />
    </xsl:if>
  </xsl:template>

  <xsl:template match="input[@type='select' and @mode='set']">
    <fieldset class="control select">
      <xsl:if test="@title">
        <legend>
          <span>
            <xsl:value-of select="@title" />
          </span>
        </legend>
      </xsl:if>
      <input type="hidden" name="{@name}[__reset]" value="1" />
      <xsl:for-each select="option[@value]">
        <label>
          <input type="checkbox" name="{../@name}[]" value="{@value}">
            <xsl:if test="@selected">
              <xsl:attribute name="checked">checked</xsl:attribute>
            </xsl:if>
          </input>
          <xsl:value-of select="text()" />
        </label>
      </xsl:for-each>
    </fieldset>
    <xsl:apply-templates select="." mode="help" />
  </xsl:template>

  <xsl:template match="input[@type='textarea']">
    <label class="control">
      <xsl:apply-templates select="." mode="label" />
      <textarea name="{@name}">
        <xsl:attribute name="class">
          <xsl:text>text resizable</xsl:text>
          <xsl:if test="@class">
            <xsl:text> </xsl:text>
            <xsl:value-of select="@class" />
          </xsl:if>
        </xsl:attribute>
        <xsl:if test="@rows">
          <xsl:attribute name="rows">
            <xsl:value-of select="@rows" />
          </xsl:attribute>
        </xsl:if>
        <xsl:if test="@cols">
          <xsl:attribute name="cols">
            <xsl:value-of select="@cols" />
          </xsl:attribute>
        </xsl:if>
        <xsl:value-of select="text()" />
      </textarea>
    </label>
    <xsl:apply-templates select="." mode="help" />
  </xsl:template>

  <xsl:template match="input[@type='checkbox']">
    <label class="control">
      <input type="checkbox" name="{@name}">
        <xsl:attribute name="vale">
          <xsl:value-of select="@value" />
          <xsl:if test="not(@value)">
            <xsl:text>1</xsl:text>
          </xsl:if>
        </xsl:attribute>
        <xsl:if test="checked">
          <xsl:attribute name="checked">checked</xsl:attribute>
        </xsl:if>
      </input>
      <span>
        <xsl:value-of select="@title" />
      </span>
    </label>
  </xsl:template>

  <xsl:template match="input[@type='file']">
    <label class="control">
      <xsl:apply-templates select="." mode="label" />
      <input type="file" name="{@name}" />
    </label>
    <xsl:apply-templates select="." mode="help" />
  </xsl:template>

  <xsl:template match="input[@type='submit']">
    <input type="submit" value="{@text}" class="control button" />
  </xsl:template>

  <xsl:template match="input[@type='hidden']">
    <input type="hidden" name="{@name}" value="{@value}" />
  </xsl:template>

  <!-- ВСПОМОГАТЕЛЬНЫЕ ШАБЛОНЫ -->

  <xsl:template match="input" mode="label">
    <xsl:if test="@title">
      <span class="t">
        <xsl:value-of select="@title" />
        <xsl:text>:</xsl:text>
        <xsl:if test="@required">
          <xsl:text>*</xsl:text>
        </xsl:if>
      </span>
    </xsl:if>
  </xsl:template>

  <xsl:template match="input" mode="help">
    <xsl:if test="@description">
      <div class="help">
        <xsl:value-of select="@description" disable-output-escaping="yes" />
      </div>
    </xsl:if>
  </xsl:template>
</xsl:stylesheet>
